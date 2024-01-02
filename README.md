# Turnstile for Typecho

适用于 Typecho 的 Turnstile 人机验证插件。

Tips: 插件需要 jQuery 才能正常运行，如没有其他主题/插件使用了 jQuery 则你需要再自定义代码中手动引入。

## 使用教程

[https://blog.nkxingxh.top/archives/240/](https://blog.nkxingxh.top/archives/240/)

## 登录验证

在插件设置中启用即可。

## 评论验证

在插件设置中启用，并在适当位置添加以下代码

```
<?php Turnstile_Plugin::output(); ?>
```
