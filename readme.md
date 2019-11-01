# laravel-example
基于 Laravel，集成常用功能。相关资料可在[我的文章](https://segmentfault.com/u/haoyq/articles)中查看。

## 安装
1. 搭建 [Laradock](https://github.com/laradock/laradock) 环境
2. 根据下方所示开启相关容器 `docker-compose up -d nginx mysql ...`
3. 在 `workspace` 中依次执行
    1. `git clone https://github.com/haoyq02/laravel-example.git`
    2. `composer install`
    3. `npm install`、`npm run dev`
    4. `cp .env.example .env`
    5. `php artisan key:generate`
4. 修改 `.env` 中 `redis`、`mysql` 相关配置，`php artisan migrate`
5. 服务器、域名配置   

## Laradock Container
环境使用 [Laradock](https://github.com/laradock/laradock) 搭建，已使用如下容器
* nginx
* php-fpm
* mysql
* workspace
* redis
* memcached
* php-worker
* rabbitmq

## Composer Package
| 名称 | 简介 | 备注 |
| ---- | ---- | ---- |
| [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) | 页面调试工具 | dev |
| [Laravel IDE Helper Generator](https://github.com/barryvdh/laravel-ide-helper) | IDE 开发工具 | dev |
| [Laravel UI](https://github.com/laravel/ui) | Bootstrap 和 Vue 脚手架 |  |
| [Laravel Horizon](https://github.com/laravel/horizon) | 队列系统 |  |
| [RabbitMQ Queue](https://github.com/vyuldashev/laravel-queue-rabbitmq#rabbitmq-queue-driver-for-laravel) | RabbitMQ 队列驱动 |  |
| [Laravel-lang](https://github.com/overtrue/laravel-lang) | 语言包 |  |
| [Larastan](https://github.com/nunomaduro/larastan) | 代码静态检查 | dev |
| [laravel-db-snapshots](https://github.com/spatie/laravel-db-snapshots) | 数据库备份工具 | |
