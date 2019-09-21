# laravel-example
基于 Laravel，集成常用功能。相关资料可在[我的文章](https://segmentfault.com/u/haoyq/articles)中查看。

## 安装
1. 搭建 [Laradock](https://github.com/laradock/laradock) 环境
2. 根据下方所示 build 并且开启相关容器 `docker-compose build nginx mysql ...`、`docker-compose up -d nginx mysql ...`
3. 在 `workspace` 中依次执行
    1. `git clone https://github.com/haoyq02/laravel-example.git`
    2. `composer install`
    3. `cp .env.example .env`
    4. `php artisan key:generate`
4. 修改 `.env` 中 `redis`、`mysql` 相关配置，`php artisan migrate`
5. 配置域名，Nginx 配置可以参考[这里](https://github.com/hhxsv5/laravel-s/blob/master/README-CN.md#%E4%B8%8Enginx%E9%85%8D%E5%90%88%E4%BD%BF%E7%94%A8%E6%8E%A8%E8%8D%90)。配置完成后记得重启服务器   
6. 回到 `workspace` 中启动 `LaravelS`, `php bin/laravels start`


## Laradock container
环境使用 [Laradock](https://github.com/laradock/laradock) 搭建，已使用如下容器
* nginx
* php-fpm
* mysql
* workspace
* redis
* memcached

## Composer package
| 名称 | 简介 | 备注 |
| ---- | ---- | ---- |
| [LaravelS](https://github.com/hhxsv5/laravel-s) | LaravelS是一个胶水项目，用于快速集成Swoole到Laravel或Lumen，然后赋予它们更好的性能、更多可能性。|  |
| [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) | 调试工具 | dev |
| [Laravel-lang](https://github.com/overtrue/laravel-lang) | 语言包 |  |
