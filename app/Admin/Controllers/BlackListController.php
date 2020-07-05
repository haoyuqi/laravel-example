<?php

namespace App\Admin\Controllers;

use App\Models\BlackList;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

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
        $grid->column('deleted_at', __(BlackList::$alias['deleted_at']));
        $grid->column('created_at', __(BlackList::$alias['created_at']));
        $grid->column('updated_at', __(BlackList::$alias['updated_at']));

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
        $show->field('deleted_at', __(BlackList::$alias['deleted_at']));
        $show->field('created_at', __(BlackList::$alias['created_at']));
        $show->field('updated_at', __(BlackList::$alias['updated_at']));

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
