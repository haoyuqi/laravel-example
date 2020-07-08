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

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __(BlackList::$alias['id']))->display(function ($id) {
            return '<a href="' . url('admin/black_list/' . $id) . '" target="_blank" >' . $id . '</a>';
        })->sortable();
        $grid->column('ip', __(BlackList::$alias['ip']));
        $grid->column('city.city', __(Visitor::$alias['city']));
        $grid->column('today_logs_count', __(BlackList::$alias['today_logs_count']))
            ->display(function () {
                return $this->logs->filter(function ($item, $key) {
                    return Carbon::parse($item['created_at'])->isToday();
                })->count();
            })->sortable();
        $grid->column('all_logs_count', __(BlackList::$alias['all_logs_count']))
            ->display(function () {
                return count($this->logs);
            })->sortable();
        $grid->column('urls', __(BlackList::$alias['urls']))
            ->expand(function ($model) {
                return new Table([__(BlackListLog::$alias['url']), __(BlackListLog::$alias['created_at'])],
                    $this->logs
                        ->take(10)
                        ->map(function ($item) {
                            return $item->only(['url', 'created_at']);
                        })
                        ->toArray()
                );
            });
        $grid->column('created_at', __(BlackList::$alias['created_at']));
        $grid->column('last_time', __(BlackList::$alias['last_time']))
            ->display(function () {
                return (string)$this->logs->sortByDesc('created_at')->first()->created_at;
            })->sortable();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('ip', __(BlackList::$alias['ip']));
            $filter->like('city.city', __(Visitor::$alias['city']));
            $filter->between('created_at', __(BlackList::$alias['created_at']))->datetime();
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

        $show->logs(__(BlackList::$alias['urls']), function ($logs) {
            $logs->resource('/admin/black_list_logs');

            $logs->model()->orderBy('id', 'desc');

            $logs->column('id', __(BlackListLog::$alias['id']));
            $logs->column('url', __(BlackListLog::$alias['url']));
            $logs->column('created_at', __(BlackListLog::$alias['created_at']))->sortable();

            $logs->disableActions();

            $logs->disableCreateButton();

            $logs->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->like('url', __(BlackListLog::$alias['url']));
                $filter->between('created_at', __(BlackListLog::$alias['created_at']))->datetime();
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
