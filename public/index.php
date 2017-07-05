<?php

/**
 * A super-simple user-authentication solution, embedded into a small framework.
 * 一个超级用户身份验证解决方案,嵌入到一个小框架。
 *
 * HUGE
 * 巨大的
 *
 * @link https://github.com/panique/huge
 * @license http://opensource.org/licenses/MIT MIT License 麻省理工学院的许可
 */

// auto-loading the classes (currently only from application/libs) via Composer's PSR-4 auto-loader
// later it might be useful to use a namespace here, but for now let's keep it as simple as possible
// 自动负载类(目前仅从application/libs)通过作曲家PSR-4装载器后在这里使用一个名称空间可能有用,但现在让我们保持尽可能简单
require '../vendor/autoload.php';

// start our application
// 开始我们的应用程序
new Application();
