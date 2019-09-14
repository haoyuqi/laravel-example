# laravel-example
基于 Laravel，集成常用功能。相关资料可在[我的文章](https://segmentfault.com/u/haoyq/articles)中查看。

## 安装
1. 搭建 [Laradock](https://github.com/laradock/laradock) 环境
2. 根据下方所示 build 并且开启相关容器 `docker-compose build nginx mysql ...`
、`docker-compose up -d nginx mysql ...`

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
