<?php

namespace App\Admin\Controllers;

use App\Models\BlackList;
use App\Models\BlackListLog;
use App\Models\Visitor;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class BlackListController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '黑名单';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlackList());

        $grid->column('id', __(BlackList::$alias['id']));
        $grid->column('ip', __(BlackList::$alias['ip']));
        $grid->column('city.city', Visitor::$alias['city']);
        $grid->column('logs', '今日访问数量')->display(function ($logs) {
            return collect($logs)->filter(function ($item, $key) {
                return Carbon::parse($item['created_at'])->isToday();
            })->count();
        })->sortable();
        $grid->column('all-logs', '历史访问数量')->display(function () {
            return count($this->logs);
        })->sortable();
        $grid->column('urls', '访问记录')->expand(function ($model) {
            return new Table([BlackListLog::$alias['url'], BlackListLog::$alias['created_at']],
                collect($this->logs)
                    ->take(10)
                    ->map(function ($item) {
                        return $item->only(['url', 'created_at']);
                    })
                    ->toArray()
            );
        });
        $grid->column('created_at', __(BlackList::$alias['created_at']));
        $grid->column('updated_at', __(BlackList::$alias['updated_at']));

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('ip', BlackList::$alias['ip']);
            $filter->like('city.city', Visitor::$alias['city']);
            $filter->between('created_at', BlackList::$alias['created_at'])->datetime();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(BlackList::findOrFail($id));

        $show->field('id', __(BlackList::$alias['id']));
        $show->field('ip', __(BlackList::$alias['ip']));
        $show->field('created_at', __(BlackList::$alias['created_at']));
        $show->field('updated_at', __(BlackList::$alias['updated_at']));

        $show->logs('访问记录', function ($logs) {
            $logs->resource('/admin/black-list-logs');

            $logs->column('id', __(BlackListLog::$alias['id']));
            $logs->column('url', __(BlackListLog::$alias['url']));
            $logs->column('created_at', __(BlackListLog::$alias['created_at']))->sortable();
            $logs->column('updated_at', __(BlackListLog::$alias['updated_at']));

            $logs->disableActions();

            $logs->disableCreateButton();

            $logs->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->like('url', BlackListLog::$alias['url']);
                $filter->between('created_at', BlackListLog::$alias['created_at'])->datetime();
            });
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BlackList());

        $form->ip('ip', __(BlackList::$alias['ip']));

        return $form;
    }
}
