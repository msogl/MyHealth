<?php

namespace Myhealth\Controllers;

use Myhealth\Daos\FyiDAO;
use Myhealth\Classes\View;
use Myhealth\Models\FyiModel;
use Myhealth\Classes\Permission;
use Myhealth\Classes\AjaxResponse;

class FyiController
{
    public function messages()
    {
        $records = (new FyiModel())->getMessages(_session('loggedInMemberId'));
        View::render('fyi', 'FYI', [ 'records' => &$records ]);
    }

    public function edit()
    {
        $id = Request('id');

        $fyiDao = ($id !== '' ? (new FyiModel())->load(DecryptAESMSOGL($id)) : new FyiDAO());
        if (is_null($fyiDao)) {
            $this->messages();
            return;
        }

        View::render('fyi-edit', 'Edit FYI', [ 'fyiDao' => &$fyiDao ]);
    }

    public function save()
    {
        $id = Request('id');
        if ($id == '') {
            AjaxResponse::error('Could not save');
        }

        $id = (int) DecryptAESMSOGL($id);

        $fyiDao = new FyiDAO();
        $fyiDao->id = $id;
        $fyiDao->Subject = Request('subject');
        $fyiDao->StartDate = Request('startdate');;
        $fyiDao->EndDate = Request('enddate');
        $fyiDao->Payor = Request('payor');
        $fyiDao->Content = Request('content');
        (new FyiModel())->save($fyiDao);
        AjaxResponse::success();
    }

    public function delete()
    {
        $id = Request('id');
        if ($id == '') {
            AjaxResponse::error('Delete failed');
        }

        $id = (int) DecryptAESMSOGL($id);
        if ($id == 0) {
            AjaxResponse::error('Delete failed');
        }
        
        (new FyiModel())->delete($id);
        AjaxResponse::success();
    }
}
