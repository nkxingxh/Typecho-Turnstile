<?php

/**
 * Turnstile 人机验证插件
 *
 * @package Turnstile
 * @author NKXingXh
 * @version 1.2.1
 * @link https://blog.nkxingxh.top/
 */

use Typecho\Common;
use Utils\PasswordHash;

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
        Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'verifyTurnstile_comment');

        Typecho_Plugin::factory('admin/footer.php')->end = array(__CLASS__, 'output_login');
        Typecho_Plugin::factory('Widget_User')->hashValidate = array(__CLASS__, 'verifyTurnstile_login');
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
        $enableActions = new Typecho_Widget_Helper_Form_Element_Checkbox('enableActions', [
            "login" => _t('登录'),
            "comment" => _t('评论')
        ], array(), _t('在哪些地方启用验证'), _t('给评论启用验证后需要修改主题模板, 查看<a href="https://blog.nkxingxh.top/archives/240/">教程</a>'));
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
        $form->addInput($enableActions);
        $form->addInput($theme);
        $form->addInput($strictMode);
    }

    /**
     * 展示验证码
     */
    public static function output()
    {
        if (!in_array('comment', Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->enableActions)) {
            return;
        }
        $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->siteKey;
        if ($siteKey != "") {
            $theme = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->theme;
            $action = 'comment';
            echo <<<EOF
            <!--script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script-->
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback" async defer></script>
            <script id="typecho-turnstile-script">
            window.onloadTurnstileCallback=function(){\$('#cf-turnstile').html('');eval(function(p,a,c,k,e,r){e=String;if('0'.replace(0,e)==0){while(c--)r[e(c)]=k[c];k=[function(e){return r[e]||e}];e=function(){return'[1-4]'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('console.log(\\' %c Turnstile for Typecho %c https://blog.nkxingxh.top/archives/240/\\',\\'1:white;2:#31655f;3:4 0\\',\\'1:#eee;2:#444;3:4\\');',[],5,'|color|background|padding|5px'.split('|'),0,{}));turnstile.render('#cf-turnstile',{sitekey:'$siteKey',theme:'$theme',action:'$action',callback:function(token){console.log(`Challenge Success`)},})};
            </script>
            <div id="cf-turnstile">正在加载验证组件</div>
            <!--div class="cf-turnstile" data-sitekey="$siteKey" data-theme="$theme"></div-->
EOF;
        } else {
            throw new Typecho_Widget_Exception(_t('No Turnstile Site Key! Please set it.'));
        }
    }

    public static function output_login()
    {
        // 判断是否登录页面
        $currentRequestUrl = Typecho_Widget::widget('Widget_Options')->request->getRequestUrl();
        if (
            !stripos($currentRequestUrl, 'login.php') ||
            !in_array('login', Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->enableActions)
        ) {
            return;
        }

        $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->siteKey;
        if ($siteKey != "") {
            $theme = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->theme;
            $action = 'login';
            echo <<<EOF
            <!--script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script-->
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback" async defer></script>
            <script>
                \$('#password').parent().after('<div id="cf-turnstile">正在加载验证组件</div>');window.onloadTurnstileCallback=function(){\$('#cf-turnstile').html('');eval(function(p,a,c,k,e,r){e=String;if('0'.replace(0,e)==0){while(c--)r[e(c)]=k[c];k=[function(e){return r[e]||e}];e=function(){return'[1-4]'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\\\b'+e(c)+'\\\\b','g'),k[c]);return p}('console.log(\\' %c Turnstile for Typecho %c https://blog.nkxingxh.top/archives/240/\\',\\'1:white;2:#31655f;3:4 0\\',\\'1:#eee;2:#444;3:4\\');',[],5,'|color|background|padding|5px'.split('|'),0,{}));turnstile.render('#cf-turnstile',{sitekey:'$siteKey',theme:'$theme',action:'$action',callback:function(token){console.log(`Challenge Success`)},})};
                //$('#password').parent().after('<div class="cf-turnstile" data-sitekey="$siteKey" data-theme="$theme"></div>');
            </script>
EOF;
        } else {
            throw new Typecho_Widget_Exception(_t('No Turnstile Site Key! Please set it.'));
        }
    }

    public static function verifyTurnstile_comment($comments, $obj)
    {
        $userObj = $obj->widget('Widget_User');
        if (($userObj->hasLogin() && $userObj->pass('administrator', true)) ||
            !in_array('comment', Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->enableActions)
        ) {
            return $comments;
        } elseif (isset($_POST['cf-turnstile-response'])) {
            //$siteKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->siteKey;
            $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->secretKey;
            $strictMode = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->strictMode == 'enable';

            if (empty($_POST['cf-turnstile-response'])) {
                throw new Typecho_Widget_Exception(_t('请先完成验证'));
            }
            $resp = self::getTurnstileResult($_POST['cf-turnstile-response'], $secretKey, $strictMode);
            if ($resp['success']) {
                if ($resp['action'] == 'comment') {
                    return $comments;
                } else {
                    throw new Typecho_Widget_Exception(_t(self::getTurnstileResultMsg('场景验证失败')));
                }
            } else {
                throw new Typecho_Widget_Exception(_t(self::getTurnstileResultMsg($resp)));
            }
        } else {
            throw new Typecho_Widget_Exception(_t('加载验证码失败, 请检查你的网络'));
        }
    }

    public static function verifyTurnstile_login($password, $hash)
    {
        $enableTurnstile = in_array('login', Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->enableActions);
        if ($enableTurnstile) {
            if (isset($_POST['cf-turnstile-response'])) {
                if (empty($_POST['cf-turnstile-response'])) {
                    Typecho_Widget::widget('Widget_Notice')->set(_t('请先完成验证'), 'error');
                    Typecho_Widget::widget('Widget_Options')->response->goBack();
                }
                $secretKey = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->secretKey;
                $strictMode = Typecho_Widget::widget('Widget_Options')->plugin('Turnstile')->strictMode == 'enable';
                $resp = self::getTurnstileResult($_POST['cf-turnstile-response'], $secretKey, $strictMode);
                if ($resp['success']) {
                    if ($resp['action'] != 'login') {
                        self::loginFailed(self::getTurnstileResultMsg('场景验证失败'));
                        return false;
                    }
                    //return true;
                } else {
                    self::loginFailed(self::getTurnstileResultMsg($resp));
                    return false;
                }
            } else {
                self::loginFailed('请等待人机验证加载完成');
                return false;
            }
        }

        /**
         * 参考 /var/Widget/User.php 中的 login 方法
         * 
         * https://github.com/typecho/typecho/blob/master/var/Widget/User.php
         */
        if ('$P$' == substr($hash, 0, 3)) {
            $hasher = new PasswordHash(8, true);
            $hashValidate = $hasher->checkPassword($password, $hash);
        } else {
            $hashValidate = Common::hashValidate($password, $hash);
        }
        return $hashValidate;
    }

    private static function loginFailed($msg)
    {
        Typecho_Widget::widget('Widget_Notice')->set(_t($msg), 'error');
        //Typecho_Widget::widget('Widget_User')->logout();
        Typecho_Widget::widget('Widget_Options')->response->goBack();
    }

    private static function getTurnstileResult($turnstile_response, $secretKey, $strictMode = false)
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
        $response = json_decode($response, true);
        return $response;
    }

    private static function getTurnstileResultMsg($resp)
    {
        if ($resp['success'] == true) {
            return '验证通过';
        } else {
            switch (strtolower($resp['error-codes'][0])) {
                case 'missing-input-response':
                    return '请先完成验证';

                case 'invalid-input-response':
                    return '验证无效或已过期';

                case 'timeout-or-duplicate':
                    return '验证响应已被使用, 请重新验证';

                case 'bad-request':
                    return '照理说不会出现这个问题的, 再试一次?';

                case 'internal-error':
                    return '验证服务器拉了, 再试一次吧';

                case 'missing-input-secret':
                case 'invalid-input-secret':
                    return '未设置或设置了无效的 secretKey';

                default:
                    return '你食不食人啊? (恼) 如果是的话再试一次?';
            }
        }
    }
}
