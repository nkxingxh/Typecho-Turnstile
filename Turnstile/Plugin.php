<?php

/**
 * Turnstile 人机验证插件
 *
 * @package Turnstile
 * @author NKXingXh
 * @version 1.0.0
 * @link https://blog.nkxingxh.top/archives/240/
 */


class Turnstile_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'filter');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $siteKeyDescription = _t("Please create a site in <a href='https://dash.cloudflare.com/'>Cloudflare Turnstile</a>");
        $siteKey = new Typecho_Widget_Helper_Form_Element_Text('siteKey', NULL, '', _t('Site Key'), $siteKeyDescription);
        $secretKey = new Typecho_Widget_Helper_Form_Element_Text('secretKey', NULL, '', _t('Serect Key'), _t(''));
        $theme = new Typecho_Widget_Helper_Form_Element_Radio('theme', array(
            'auto' => '自动',
            'light' => '亮色',
            'dark' => '暗色'
        ), 'auto', _t('主题'), _t(''));
        $strictMode = new Typecho_Widget_Helper_Form_Element_Radio('strictMode', array(
            'enable' => '启用',
            'disable' => '禁用'
        ), 'disable', _t('严格模式'), _t('启用后将会严格判断提交评论与验证时使用的IP是否一致'));
        $form->addInput($siteKey);
        $form->addInput($secretKey);
        $form->addInput($theme);
        $form->addInput($strictMode);
    }

    /**
     * 展示验证码
     */
    public static function output()
    {
        $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->siteKey;
        $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->secretKey;
        $theme = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->theme;
        if ($siteKey != "" && $secretKey != "") {
            echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <script>console.log(\' %c Turnstile for Typecho %c https://blog.nkxingxh.top/archives/240/\',\'color:white;background:#31655f;padding:5px 0\',\'color:#eee;background:#444;padding:5px\');</script>
                               <div class="cf-turnstile" data-sitekey="' . $siteKey . '" data-theme="' . $theme . '"></div>';
        } else {
            throw new Typecho_Widget_Exception(_t('No Turnstile Site/Secret Keys! Please set it/them!'));
        }
    }

    public static function filter($comments, $obj)
    {
        $userObj = $obj->widget('Widget_User');
        if ($userObj->hasLogin() && $userObj->pass('administrator', true)) {
            return $comments;
        } elseif (isset($_POST['cf-turnstile-response'])) {
            $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->siteKey;
            $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->secretKey;
            $strictMode = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->strictMode == 'enable';
            function getTurnstileResult($turnstile_response, $secretKey, $strictMode = false)
            {
                $payload = array('secret' => $secretKey, 'response' => $turnstile_response);
                if ($strictMode) $payload['remoteip'] = $_SERVER['REMOTE_ADDR'];
                $stream = stream_context_create(array(
                    'http' => array(
                        'method' => 'POST',
                        'content' => http_build_query($payload)
                    )
                ));
                $response = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, $stream);
                $response = json_decode($response);
                return $response;
            }
            if (empty($_POST['cf-turnstile-response'])) {
                throw new Typecho_Widget_Exception(_t('请先完成验证'));
            }
            $resp = getTurnstileResult($_POST['cf-turnstile-response'], $secretKey, $strictMode);
            if ($resp->success == true) {
                return $comments;
            } else {
                switch ($resp->error - codes) {
                    case '{[0] => "missing-input-response"}':
                        throw new Typecho_Widget_Exception(_t('请先完成验证'));
                        break;

                    case '{[0] => "invalid-input-response"}':
                        throw new Typecho_Widget_Exception(_t('验证无效或已过期'));
                        break;

                    case '{[0] => "timeout-or-duplicate"}':
                        throw new Typecho_Widget_Exception(_t('验证响应已被使用, 请重新验证'));
                        break;

                    case '{[0] => "bad-request"}':
                        throw new Typecho_Widget_Exception(_t('照理说不会出现这个问题的, 再试一次?'));
                        break;

                    case '{[0] => "internal-error"}':
                        throw new Typecho_Widget_Exception(_t('验证服务器拉了, 再试一次吧'));
                        break;

                    case '{[0] => "missing-input-secret"}':
                    case '{[0] => "invalid-input-secret"}':
                        throw new Typecho_Widget_Exception(_t('未设置或设置了无效的 siteKey/secretKey'));
                        break;

                    default:
                        throw new Typecho_Widget_Exception(_t('你食不食人啊? (恼) 如果是的话再试一次?'));
                }
            }
        } else {
            throw new Typecho_Widget_Exception(_t('加载验证码失败, 请检查你的网络'));
        }
    }
}
