# Turnstile for Typecho

适用于 Typecho 的 Turnstile 人机验证插件。

Tips: 插件需要 jQuery 才能正常运行，如没有其他主题或插件引入了 jQuery 则需要在插件设置在启用引入 jQuery。

## 使用教程

[https://blog.nkxingxh.top/archives/240/](https://blog.nkxingxh.top/archives/240/)

## 登录验证

在插件设置中启用即可。

## 评论验证

在插件设置中启用，并在适当位置添加以下代码

```
<?php Turnstile_Plugin::output(); ?>
```

## 异常处理

如果服务器断网或者插件配置存在问题无法通过验证，导致无法登录 Typecho 后台，请参考以下方法。

打开 `Plugin.php`，查找 `private const RESCUE_MODE`，将其值改为 `true`。

此时将启用救援模式，插件将会跳过登录时的 Turnstile 验证。

你就可以登录后台，排查问题或禁用插件。

问题排查完毕，别忘记把 `RESCUE_MODE` 设置为 `false`。
