<?php
/**
 * 模块微站定义
 *
 * @author 老虎
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');
require_once IA_ROOT . '/addons/task_tiger/lib/KdtApiOauthClient.php';

class task_tigerModuleSite extends WeModuleSite
{


    public function downloadImageFromQzone($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);

        curl_close($ch);
        $imageAll = array_merge(array('imgBody' => $package), $httpinfo);
        //return $imageAll;
        $filename = IA_ROOT . '/addons/task_tiger/' . time() . ".jpg";
        $local_file = fopen($filename, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $imageAll["imgBody"])) {
                fclose($local_file);
            }
        }
        return $filename;
    }


    public function doWebCs()
    {
        global $_W, $_GPC;

        $url = "http://wx.qlogo.cn/mmopen/ajNVdqHZLLD8wULlHMYicmTvbOeBlJGxzQLyZJz2dAf8ov7alKmxEuPEiaQh5I1fEM2TsMJ5paY7b3eBWBichTobQ/0";
        $imageAll = $this->downloadImageFromQzone($url);
        echo $imageAll;
        echo 'ss';
        exit;


        $token = $this->accesstoken();
        $client = new KdtApiOauthClient();
        $method = 'kdt.users.weixin.follower.tags.add';
        $params = [
            'weixin_openid' => 'oozm3t8q7pk9LB2gn7iOLUl8E73U',
            'tags' => '测试标签',
        ];

        $json = $client->post($token, $method, $params);
        echo '<pre>';
        print_r($json);
        exit;
    }

    public function doMobileCs1()
    {
        global $_W, $_GPC;
        $poster = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where id=1");
        $tp_value1 = unserialize($poster['tp_value1']);
        $tp_color1 = unserialize($poster['tp_color1']);

        $tplist1 = array(
            'first' => array(
                'value' => $poster['tp_first1'],
                "color" => $poster['firstcolor1']
            )
        );

        foreach ($tp_value1 as $key => $value) {
            if (empty($value)) {
                continue;
            }
            //$key++;
            $tplist1['keyword' . $key] = array('value' => $value, 'color' => $tp_color1[$key]);
        }
        $tplist1['remark'] = array(
            'value' => $poster['tp_remark1'],
            "color" => $poster['remarkcolor1']
        );
        //$tplist1=array_unshift($tplist1,$first);
        echo '<pre>';
        print_r($tplist1);
        exit;
    }


    /********************************************有赞免刷新，获取TOKEN******************************************************************/
    public function accesstoken()
    {//使用TOKEN
        global $_W, $_GPC;
        $tk = pdo_fetch('select * from ' . tablename($this->modulename . "_token") . " where weid='{$_W['uniacid']}'");
        if ($tk['endtime'] >= TIMESTAMP) {//token 过期重新刷新获得
            $t = $this->RefreshToken($tk['refresh_token']);
            $data = array(
                'access_token' => $t['access_token'],
                'refresh_token' => $t['refresh_token'],
                'expires_in' => $t['expires_in'],
                'scope' => $t['scope'],
                'token_type' => $t['token_type'],
                'endtime' => TIMESTAMP + $t['expires_in'],
                'createtime' => TIMESTAMP,

            );
            pdo_update($this->modulename . "_token", $data, array('id' => $tk['id']));
            return $t['access_token'];
        } else {
            return $tk['access_token'];
        }
    }


    public function doWebYzauth()
    {//打开授权
        global $_W, $_GPC;
        //$cfg=$this->module['config'];
        $setting = $_W['config']['setting'];
        if (empty($setting['client_id'])) {
            echo '请先联系管理员，设置有赞client_id';
            exit;
        }

        $client_id = $setting['client_id'];
        $weid = $_W['uniacid'];
        $redirect_uri = $_W['siteroot'] . 'addons/task_tiger/yztoken.php?i=' . $_W['uniacid'];
        // $redirect_uri=$_W['siteroot'].'addons/task_tiger/yztoken.php';
        $url = "https://open.koudaitong.com/oauth/authorize?client_id=" . $client_id . "&response_type=code&state=renwubao&redirect_uri=" . $redirect_uri . "";
        header("location:" . $url);//跳转到 获取token页面
        exit;
    }

    public function doMobiletoken()
    {//获取CODE
        global $_W, $_GPC;
        //第一部打开链接授权 获取CODE
        if (empty($_GPC['code'])) {
            echo '授权失败，请查看参数设置，参数不能有空格，是否和有赞的一样，';
            exit;
        }
        $code = $_GPC['code'];
        $tk = $this->token($code);
        if (!empty($tk['error'])) {
            echo '错误代码:' . $tk['error'] . '<Br>' . $tk['error_description'];
            exit;
        }
        //echo '<pre>';
        //print_r($tk);
        //exit;
        //获取的TOKEN保存
        $token = pdo_fetch('select * from ' . tablename($this->modulename . "_token") . " where weid='{$_W['uniacid']}'");
        if (empty($token)) {
            $data = array(
                'weid' => $_W['uniacid'],
                'access_token' => $tk['access_token'],
                'refresh_token' => $tk['refresh_token'],
                'expires_in' => $tk['expires_in'],
                'scope' => $tk['scope'],
                'token_type' => $tk['token_type'],
                'endtime' => TIMESTAMP + $tk['expires_in'],
                'createtime' => TIMESTAMP,

            );
            pdo_insert($this->modulename . "_token", $data);
        } else {
            $data = array(
                'access_token' => $tk['access_token'],
                'refresh_token' => $tk['refresh_token'],
                'expires_in' => $tk['expires_in'],
                'scope' => $tk['scope'],
                'token_type' => $tk['token_type'],
                'endtime' => TIMESTAMP + $tk['expires_in'],
                'createtime' => TIMESTAMP,

            );
            pdo_update($this->modulename . "_token", $data, array('id' => $token['id']));
        }
        message('授权成功！', $_W['siteroot'] . '/web/index.php?c=profile&a=module&do=setting&m=task_tiger', 'success');

    }

    public function token($code)
    {//获取token 7天有效
        global $_W, $_GPC;
        load()->func('communication');
        //$cfg=$this->module['config'];
        $setting = $_W['config']['setting'];
        $client_id = $setting['client_id'];
        $client_secret = $setting['client_secret'];
        $weid = $_W['uniacid'];
        $redirect_uri = $_W['siteroot'] . 'addons/task_tiger/yztoken.php?i=' . $_W['uniacid'];
        $tkurl = "https://open.koudaitong.com/oauth/token?client_id=" . $client_id . "&client_secret=" . $client_secret . "&grant_type=authorization_code&code=" . $code . "&redirect_uri=" . $redirect_uri . "";
        $to = ihttp_get($tkurl);
        $auth = @json_decode($to['content'], true);
        return $auth;
        /*获得TOKNE
         Array
            (
                [access_token] => bb16c620c50f3c1cada1517059a957c9
                [expires_in] => 604800   //7天有效期,过期重新获取
                [refresh_token] => 086abc0160ab30f682d0b8efc5ebe8d9  //过期时间：28 天
                [scope] => item trade trade_virtual user utility shop item_category logistics pay_qrcode coupon present_advanced item_category_advanced
                [token_type] => Bearer
            )
         */
    }


    public function RefreshToken($refresh_token)
    {//token过期后，刷新refresh_token，获得新的token
        global $_W, $_GPC;
        load()->func('communication');
        //$cfg=$this->module['config'];
        $setting = $_W['config']['setting'];
        $client_id = $setting['client_id'];
        $client_secret = $setting['client_secret'];
        $tkurl = "https://open.koudaitong.com/oauth/token?grant_type=refresh_token&refresh_token=" . $refresh_token . "&client_id=" . $client_id . "&client_secret=" . $client_secret . "";
        $to = ihttp_get($tkurl);
        $auth = @json_decode($to['content'], true);
        return $auth;
        /*
      Array
        (
            [access_token] => b9581b76660730feb1e2577dc1afb6e1
            [expires_in] => 604800
            [refresh_token] => e07f1e25abb6329abe996b6e257d5cb7
            [scope] => item trade trade_virtual user utility shop item_category logistics pay_qrcode coupon present_advanced item_category_advanced
            [token_type] => Bearer
        )*/

    }

    /********************************************有赞免刷新，获取TOKEN 结束******************************************************************/


    public function doWebOrder()
    {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 12;
        if ($_GPC['name']) {
            //$where=" and fans_id=".$_GPC['name']."";
            $name = $_GPC['name'];
            $where = " and (tel = '{$name}' or orderno = '{$name}' or usernames like '%{$name}%' or nickname like '%{$name}%' )";
        }
        $order = pdo_fetchall('select * from ' . tablename($this->modulename . "_hgoods") . " h left join " . tablename($this->modulename . "_order") . " o on o.goods_id=h.goods_id where o.cengji=0 and o.weid='{$_W['uniacid']}'   {$where} and paystate=1 order by id desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_order') . " where weid='{$_W['uniacid']}' {$where} and paystate=1");
        $pager = pagination($total, $pindex, $psize);
        //echo '<pre>';
        //print_r($order);
        //exit;

        include $this->template('order');
    }


    public function doWebTxlist()
    {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 12;
        if ($_GPC['name']) {
            //$where=" and fans_id=".$_GPC['name']."";
            $name = $_GPC['name'];
            $where = " and (tel = '{$name}' or orderno = '{$name}' or usernames like '%{$name}%' or nickname like '%{$name}%' )";
        }
        if ($_GPC['op'] == 2) {
            $op = " and txtype=2";
        } else {
            $op = " and txtype=1 or txtype=2";
        }
        $order = pdo_fetchall('select * from ' . tablename($this->modulename . "_order") . " where cengji>0 and weid='{$_W['uniacid']}'  and paystate=1  {$where} {$op} order by id desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");


        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_order') . " where cengji>0 and weid='{$_W['uniacid']}' and paystate=1  {$where} {$op}");
        $pager = pagination($total, $pindex, $psize);
        //echo '<pre>';
        //echo $op;
        // print_r($order);
        //exit;

        include $this->template('txlist');
    }


    public function doWebMPoster()
    {
        global $_W, $_GPC;
        $do = 'mposter';
        if ('delete' == $_GPC['op'] && $_GPC['id']) {
            //$rid = pdo_fetchcolumn('select rid from '.tablename($this->modulename."_poster")." where id='{$_GPC['id']}'");
            $poster = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where id='{$_GPC['id']}'");
            if (pdo_delete($this->modulename . "_poster", array('id' => $_GPC['id'])) === false) {
                message('删除海报失败！');
            } else {
                $shares = pdo_fetchall('select id from ' . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}'");
                foreach ($shares as $value) {
                    @unlink(str_replace('#sid#', $value['id'], "../addons/task_tiger/qrcode/mposter#sid#.jpg"));
                }
                $r = pdo_delete('rule', array('id' => $poster['rid']));
                pdo_delete('rule_keyword', array('rid' => $poster['rid']));
                pdo_delete($this->modulename . "_member", array('weid' => $_W['uniacid']));
                pdo_delete('qrcode', array('keyword' => $poster['kword'], 'uniacid' => $_W['uniacid']));
                message('删除海报成功！', $this->createWebUrl('mposter'));
            }
        }
//同步海报到其他公众号下
        if ('update' == $_GPC['op'] && $_GPC['id']) {
            $poster = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where id='{$_GPC['id']}'");

            //公众号列表
            $accountGroup = pdo_fetchall('select uniacid from ' . tablename("task_tiger_account") . " where `group` = 'admin' ", array());
            foreach ($accountGroup as $value){
                if($value['uniacid']==$poster['weid']){continue;}//去除自身
                $poster['weid']=$value['uniacid'];
                unset($poster['id']);
//                如果存在则更新，不存在则插入
                if ($user = pdo_get($this->modulename . "_poster", array('weid' => $poster['weid'],'rid'=>$poster['rid']), array('weid'))){
                    $result = pdo_update($this->modulename . "_poster", $poster, array(
                        'weid' => $poster['weid'],'rid'=>$poster['rid']),$glue = 'AND');
                }else{
                    pdo_insert($this->modulename . "_poster", $poster) === false;
                }
            }
        }

        $haibao = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where weid='{$_W['uniacid']}' limit 1");
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("select * from " . tablename($this->modulename . "_poster") . " p where weid='{$_W['uniacid']}' LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_poster') . " where weid='{$_W['uniacid']}'");
        $pager = pagination($total, $pindex, $psize);
        //echo '<pre>';
        //print_r($list);
        //echo $list['0']['id'];
        //exit;
        include $this->template('mlist');
    }


    public function doWebMCreate()
    {
        // 这个操作被定义用来呈现 管理中心导航菜单
        global $_W, $_GPC;
        $do = 'mcreate';
        $op = $_GPC ['op'];
        $id = $_GPC ['id'];
        $item = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where id='{$id}'");
        //echo '<pre>';
        //print_r($item);
        //exit;


        $token = pdo_fetch('select * from ' . tablename($this->modulename . "_token") . " where weid='{$_W['uniacid']}'");
        if (pdo_tableexists('ewei_shop_member_group')) {
            $ewgroup = pdo_fetchall('select * from ' . tablename("ewei_shop_member_group") . " where uniacid='{$_W['uniacid']}'");
        } else {
            $ewgroup['error'] = '人人分销商城不存在！您未安装人人商城';
        }

        if (checksubmit()) {
            $ques = $_GPC['ques'];
            $answer = $_GPC['answer'];
            $questions = '';
            foreach ($ques as $key => $value) {
                if (empty($value)) continue;
                $questions[] = array('question' => $value, 'answer' => $answer[$key]);
            }
            //echo '<pre>';
            //print_r($_GPC);
            //exit;

            $data = array(
                'weid' => $_W['uniacid'],
                'title' => $_GPC ['title'],
                'data' => htmlspecialchars_decode($_GPC ['data']),
                'createtime' => time(),
                'bg' => $_GPC ['bg'],
                'picurl' => $_GPC ['picurl'],

                'rwmb' => $_GPC ['rwmb'],
                'rwlx' => $_GPC ['rwlx'],
                'cardid' => $_GPC ['cardid'],
                'hbsl' => $_GPC ['hbsl'],
                'yzbq' => $_GPC ['yzbq'],
                'tp_value' => serialize($_GPC ['tp_value']),
                'tp_color' => serialize($_GPC ['tp_color']),
                'rwmbtxid' => $_GPC ['rwmbtxid'],
                'tp_first' => $_GPC ['tp_first'],
                'firstcolor' => $_GPC ['firstcolor'],
                'tp_remark' => $_GPC ['tp_remark'],
                'remarkcolor' => $_GPC ['remarkcolor'],
                'tp_url' => $_GPC ['tp_url'],

                'ewtype' => $_GPC ['ewtype'],
                'ewjl' => $_GPC ['ewjl'],

                'tp_value1' => serialize($_GPC ['tp_value1']),
                'tp_color1' => serialize($_GPC ['tp_color1']),
                'rwmbtxid1' => $_GPC ['rwmbtxid1'],
                'tp_first1' => $_GPC ['tp_first1'],
                'firstcolor1' => $_GPC ['firstcolor1'],
                'tp_remark1' => $_GPC ['tp_remark1'],
                'remarkcolor1' => $_GPC ['remarkcolor1'],
                'tp_url1' => $_GPC ['tp_url1'],

                'mbfont' => $_GPC ['mbfont'],
                'kword' => $_GPC ['kword'],
                'mtips' => $_GPC ['mtips'],
                'gztype' => $_GPC ['gztype'],
                'winfo1' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['winfo1']), ENT_QUOTES),
                'winfo2' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['winfo2']), ENT_QUOTES),
                'winfo3' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['winfo3']), ENT_QUOTES),
                'stitle' => serialize($_GPC ['stitle']),
                'sthumb' => serialize($_GPC ['sthumb']),
                'sdesc' => serialize($_GPC ['sdesc']),
                'surl' => serialize($_GPC ['surl']),
                'rtype' => $_GPC ['rtype'],
                'starttime' => strtotime($_GPC['starttime']),
                'endtime' => strtotime($_GPC['endtime']),
                'nostarttips' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['nostarttips']), ENT_QUOTES),
                'endtips' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['endtips']), ENT_QUOTES),
                'rscore' => serialize($_GPC ['surl']),
                'rtips' => htmlspecialchars_decode(str_replace('&quot;', '&#039;', $_GPC ['rtips']), ENT_QUOTES),
            );
            if ($id) {
                if (pdo_update($this->modulename . "_poster", $data, array(
                        'id' => $id
                    )) === false
                ) {
                    message('更新海报失败！1');
                } else {
                    if (empty($item['rid'])) {
                        $this->createRule($_GPC['kword'], $id);
                    } elseif ($item['kword'] != $data['kword']) {
                        //修改生成二维码和扫码的关键字
                        pdo_update('rule_keyword', array('content' => $data['kword']), array('rid' => $item['rid']));
                        pdo_update('qrcode', array('keyword' => $data['kword']), array('name' => $this->modulename, 'keyword' => $item['kword']));
                    }
                    message('更新海报成功！2', $this->createWebUrl('mposter'));
                }
            } else {
                $data['rtype'] = $_GPC['rtype'];
                $data ['createtime'] = time();
                if (pdo_insert($this->modulename . "_poster", $data) === false) {
                    message('生成海报失败！3');
                } else {
                    $this->createRule($_GPC['kword'], pdo_insertid());
                    message('生成海报成功！4', $this->createWebUrl('mposter'));
                }

            }
        }
        load()->func('tpl');
        if ($item) {
            $data = json_decode(str_replace('&quot;', "'", $item['data']), true);
            $size = getimagesize(toimage($item['bg']));
            $size = array($size[0] / 2, $size[1] / 2);
            $date = array('start' => date('Y-m-d H:i:s', $item['starttime']), 'end' => date('Y-m-d H:i:s', $item['endtime']));
            $titles = unserialize($item['stitle']);
            $thumbs = unserialize($item['sthumb']);
            $sdesc = unserialize($item['sdesc']);
            $surl = unserialize($item['surl']);
            $tp_value = unserialize($item['tp_value']);
            $tp_color = unserialize($item['tp_color']);
            $tp_value1 = unserialize($item['tp_value1']);
            $tp_color1 = unserialize($item['tp_color1']);
            foreach ($titles as $key => $value) {
                if (empty($value)) continue;
                $slist[] = array('stitle' => $value, 'sdesc' => $sdesc[$key], 'sthumb' => $thumbs[$key], 'surl' => $surl[$key]);
            }
            foreach ($tp_value as $key => $value) {
                if (empty($value)) continue;
                $tplist[] = array('tp_value' => $value, 'tp_color' => $tp_color[$key]);
            }
            foreach ($tp_value1 as $key => $value) {
                if (empty($value)) continue;
                $tplist1[] = array('tp_value1' => $value, 'tp_color1' => $tp_color1[$key]);
            }
        } else $date = array('start' => date('Y-m-d H:i:s', time()), 'end' => date('Y-m-d H:i:s', time() + 7 * 24 * 3600));
        //$groups = pdo_fetchall('select * from '.tablename('mc_groups')." where uniacid='{$_W['uniacid']}' order by isdefault desc");
        //echo '<pre>';
        //print_r($tplist);
        //exit;
        include $this->template('mcreate');
    }

    public function doWebAccount()
    {
        global $_W, $_GPC;
        $do = 'account';
//        删除操作
        if ('delete' == $_GPC['op'] && $_GPC['acid']) {
            //$rid = pdo_fetchcolumn('select rid from '.tablename($this->modulename."_poster")." where id='{$_GPC['id']}'");
            $poster = pdo_fetch('select * from ' . tablename($this->modulename . "_account") . " where group='admin'");
            if (pdo_delete($this->modulename . "_account", array('acid' => $_GPC['acid'])) === false) {
                message('删除海报失败！');
            } else {
                pdo_delete($this->modulename . "_account", array('acid' => $_GPC['acid']));
                message('删除海报成功！', $this->createWebUrl('account'));
            }
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("select * from " . tablename($this->modulename . "_account") . " where `group`='admin' LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_account') . " where `group`='admin'");
        $pager = pagination($total, $pindex, $psize);
        //echo '<pre>';
        //print_r($list);
        //echo $list['0']['id'];
        //exit;
        include $this->template('alist');
    }

    /**
     *
     */
    public function doWebACreate()
    {
        //
        global $_W, $_GPC;
        $do = 'acreate';
        $op = $_GPC ['op'];
        $id = $_GPC ['id'];
        if (checksubmit()) {
            $original = $_GPC['original'];
            $account = pdo_fetch('select * from ' . tablename("account_wechats") . " where original = '$original' ");

//            followList("omTTFt9IPQM6g0_CZXUxer-BEQGE");


//            echo '<pre>';
//            print_r($account);
//            exit;

            $data = array(
                'original' => $account['original'],
                'name' => $account['name'],
                'acid' => $account['acid'],
                'uniacid' => $account['uniacid'],
                'group' => $_GPC ['group'],
                'account' => $account ['account'],
            );
            if (pdo_insert($this->modulename . "_account", $data) === false) {
                message('添加公众号失败！1');
            } else {
                message('添加公众号成功！2', $this->createWebUrl('account'));
            }


        }
        load()->func('tpl');
        include $this->template('acreate');
    }


    private function createRule($kword, $pid)
    {
        global $_W;
        $rule = array(
            'uniacid' => $_W['uniacid'],
            'name' => $this->modulename,
            'module' => $this->modulename,
            'status' => 1,
            'displayorder' => 254,
        );
        pdo_insert('rule', $rule);
        unset($rule['name']);
        $rule['type'] = 1;
        $rule['rid'] = pdo_insertid();
        $rule['content'] = $kword;
        pdo_insert('rule_keyword', $rule);
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($pid.'----'.$rule['rid']),FILE_APPEND);
        pdo_update($this->modulename . "_poster", array('rid' => $rule['rid']), array('id' => $pid));
    }


    public function doWebDelete()
    {
        global $_W, $_GPC;
        $id = $_GPC['sid'];
        $pid = $_GPC['pid'];
        $qrcid = $_GPC['sceneid'];
        pdo_delete($this->modulename . "_member", array('id' => $id));
        pdo_delete("qrcode", array('qrcid' => $qrcid));
        message('删除成功！', $this->createWebUrl('hymember', array('pid' => $pid)));
    }


    public function doWebHymember()
    {
        global $_W, $_GPC;

        load()->model('mc');
        $weid = $_W['weid'];
        $id = $_GPC['id'];
        $op = $_GPC['op'];
        $pid = $_GPC['pid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        //echo '<pre>';
        //print_r($_GPC);
        //exit;
        if (empty($pid)) {
            message('活动参数错误！', $this->createWebUrl('mposter'));
        }

        $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid='{$id}'", array(), 'id');
        $count1 = count($fans1);
        if (empty($count1)) {
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
        }
        if (!empty($fans1)) {
            $fans2 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans1)) . ")", array(), 'id');
            $count2 = count($fans2);
            if (empty($count2)) {
                $count2 = 0;
            }
        }
        if (!empty($fans2)) {
            $fans3 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans2)) . ")", array(), 'id');
            $count3 = count($fans3);
            if (empty($count3)) {
                $count3 = 0;
            }
        }
        if ($_GPC['name']) {
            //$where=" and fans_id=".$_GPC['name']."";
            $name = $_GPC['name'];
            $where = " and (tel = '{$name}' or id = '{$name}' or usernames like '%{$name}%' or nickname like '%{$name}%' )";
        }


        if ($op == 1) {
            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid='{$id}' order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid='{$id}'");

        } elseif ($op == 2) {

            $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid='{$id}'", array(), 'id');
            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans1)) . ") LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            //echo '<pre>';
            //print_r($list);
            //exit;
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans1)) . ")");
        } elseif ($op == 3) {

            $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid='{$id}' order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}", array(), 'id');
            $fans2 = pdo_fetchall("select id from " . tablename($this->modulename . "_id") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans1)) . ")", array(), 'id');
            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans2)) . ") LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_share') . " where weid='{$_W['uniacid']}' and pid='{$pid}' and helpid in (" . implode(',', array_keys($fans2)) . ")");
        } else {

            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$pid}' {$where} order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and pid='{$pid}'");

        }


        //echo '<pre>';
        //print_r($list);
        //exit;

        $mlist = array();
        foreach ($list as $k => $v) {
            $cont = $this->postfanscont($v['id']);
            $zjyj = $this->getyongyin($v['id']);
            if (!empty($v['helpid'])) {
                $mc = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE id = :id", array(':id' => $v['helpid']));
            } else {
                $mc['nickname'] = '';
            }

            $jf = mc_credit_fetch($v['openid']);
            $mlist[$k]['id'] = $v['id'];
            $mlist[$k]['sceneid'] = $v['sceneid'];
            //$mlist[$k]['uid']=$v['openid'];
            $mlist[$k]['helpid'] = $v['helpid'];
            $mlist[$k]['dj'] = $v['fans_type'];
            $mlist[$k]['avatar'] = $v['avatar'];
            $mlist[$k]['openid'] = $v['openid'];
            $mlist[$k]['nickname'] = $v['nickname'];
            $mlist[$k]['usernames'] = $v['usernames'];
            $mlist[$k]['tel'] = $v['tel'];
            $mlist[$k]['time'] = $v['time'];//加入时间
            $mlist[$k]['createtime'] = $v['createtime'];//付费时间
            $mlist[$k]['province'] = $v['province'];
            $mlist[$k]['city'] = $v['city'];
            $mlist[$k]['tjrname'] = $mc['nickname'];
            $mlist[$k]['tjrid'] = $mc['id'];
            $mlist[$k]['sex'] = $v['sex'];
            $mlist[$k]['district'] = $v['district'];
            $mlist[$k]['lv1'] = $cont['count1'];
            $mlist[$k]['lv2'] = $cont['count2'];
            $mlist[$k]['lv3'] = $cont['count3'];
            $mlist[$k]['fans_type'] = $v['fans_type'];
            $mlist[$k]['follow'] = $cont['follow'];
            $mlist[$k]['zjyj'] = $zjyj;

        }


        $pager = pagination($total, $pindex, $psize);

        // $mlist=$this->postfanscont(15425);
        //var_dump($mlist);
        //echo '<pre>';
        //print_r($mlist);
        //exit;

        include $this->template('hymember');
    }

    public function getyongyin($memberid)
    {//会员总计佣金
        global $_W;
        $m = pdo_fetch("SELECT sum(price) yj FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$memberid}' and cengji>0 and paystate=1");
        //$hyyj=count($m);
        if (empty($m['yj'])) {
            $m['yj'] = '0.00';
        }
        return $m['yj'];
    }

    public function doWebMemberedit()
    {
        global $_W;
        global $_GPC;
        $pid = $_GPC['pid'];
        $id = intval($_GPC['id']);
        if (!empty($id)) {
            $item = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE id = :id", array(':id' => $id));
            if (empty($item)) {
                message('会员不存在！', '', 'error');
            }
        }

        if (checksubmit('submit')) {
            //echo '<pre>';
            //print_r($_GPC);
            //exit;
            $data = array(
                'usernames' => $_GPC['usernames'],
                'tel' => $_GPC['tel'],
                'fans_type' => $_GPC['fans_type'],
                'helpid' => $_GPC['helpid'],


            );
            pdo_update($this->modulename . "_member", $data, array('id' => $id));
            message('会员更新成功！', $this->createWebUrl('hymember', array('op' => 'display', 'pid' => $pid)), 'success');

        }


        include $this->template('memberedit');
    }

    public function postfanscont($id)
    {//统计粉丝1 2 3 级人数
        global $_W, $_GPC;
        $weid = $_W['weid'];
        // return $uid;
        $count1 = 0;
        $count2 = 0;
        $count3 = 0;

        $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$weid}' and helpid='{$id}'", array(), 'id');
        $count1 = count($fans1);

        if (!empty($fans1)) {
            $fans2 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$weid}' and helpid in (" . implode(',', array_keys($fans1)) . ")", array(), 'id');
            $count2 = count($fans2);
            if (empty($count2)) {
                $count2 = 0;
            }
        }
        if (!empty($fans2)) {
            $fans3 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$weid}' and helpid in (" . implode(',', array_keys($fans2)) . ")", array(), 'id');
            $count3 = count($fans3);
            if (empty($count3)) {
                $count3 = 0;
            }
        }
        $fcont = array('count1' => $count1, 'count2' => $count2, 'count3' => $count3);
        return $fcont;

    }


    /*
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `weid` int(11) NOT NULL,
  `from_user` varchar(100) NOT NULL,
  `from_name` varchar(100) NOT NULL,
  `openid` varchar(100) NOT NULL,
  `openid_name` varchar(100) NOT NULL,
  `lxtype` varchar(10) DEFAULT 0,
  `num` Decimal(10,2) NOT NULL DEFAULT '0',
  `module` varchar(30) NOT NULL,
  `createtime` int(10) unsigned NOT NULL,
  `remark` varchar(200) NOT NULL,
    */
    public function createjl($from_user, $type, $num, $module, $remark, $from_user_realname, $openid, $openid_name)
    {
        global $_W;
        //$this->createjl($row['from_user'],'1','0.02','task_tiger','任务奖励');
        if (empty($from_user)) {
            Return '';
        }
        $member = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and from_user = '{$from_user}'");//当前粉丝信息
        $price = $member['price'] + $num;
        if ($price < 0) {
            Return '';
        }
        $dataprice = array(
            'price' => $price,
        );
        $aa = pdo_update($this->modulename . "_member", $dataprice, array('from_user' => $from_user, 'weid' => $_W['uniacid']));//修改金额

        $data = array(
            'weid' => $_W['uniacid'],
            'from_user' => $from_user,
            'from_name' => $from_user_realname,
            'openid' => $openid,
            'openid_name' => $openid_name,
            'lxtype' => $type,
            'num' => $num,
            'module' => $module,
            'createtime' => TIMESTAMP,
            'remark' => $remark
        );
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($data),FILE_APPEND);
        $ms = pdo_insert($this->modulename . "_record", $data);//记录金额明细
        Return $aa;
    }


    public function doMobileJiameng()
    {
        global $_W, $_GPC;
        $weid = $_W['uniacid'];
        $id = $_GPC['id'];

        //任务分享注册
        $cfg = $this->module['config'];
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        //$view = pdo_fetch ( 'select * from ' . tablename ($this->modulename."_hgoods") . " where weid='{$weid}' and goods_id='{$id}'" );
        $view = pdo_fetch('select * from ' . tablename($this->modulename . "_hgoods") . " where weid='{$weid}' order by px desc");
        //echo '<pre>';
        //print_r($fa);
        //exit;
        $view['content'] = htmlspecialchars_decode($view['content']);
        $ddsum = pdo_fetchcolumn('select COUNT(*) from ' . tablename($this->modulename . "_request") . " where weid='{$weid}' and goods_id='{$id}'");
        $cysum = $view['xnjdrs'] + $ddsum;

        $uid = $W['openid'];
        if (empty($uid)) {
            $fans = mc_oauth_userinfo();
            $uid = $fans['openid'];
        }

        $goods_request = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_request") . " WHERE goods_id ='{$id}' and weid = '{$_W['uniacid']}' and from_user = '{$uid}'");//查找当前用户是否做过
        //$goods_request['status']=2;
        $rwpic = explode('|', $goods_request['image']);

        //任务分享注册
        //$helpid=$_GPC['helpid'];
        //$fans=mc_oauth_userinfo();
        //$member=$this->ismember($fans,$helpid);//当前粉丝信息
        //$helpid=$member['id'];
        //结束

        //echo floatval($view['price']);
        //echo '<pre>';
        //print_r($view);
        //exit;

        include $this->template('jiameng');
    }

    //微信源支付
    public function doMobileGetpay()
    {
        global $_GPC, $_W;
        //任务分享注册
        $cfg = $this->module['config'];
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        if (empty($member)) {
            $result = array("errcode" => 1, "errmsg" => '用户信息不存在');
            die(json_encode($result));
        }
        $goods_id = intval($_GPC['goods_id']);
        $usernames = $_GPC['usernames'];
        $tel = $_GPC['tel'];
        if (empty($usernames) || empty($tel)) {
            $result = array("errcode" => 1, "errmsg" => '用户名和手机号码不可为空');
            die(json_encode($result));
        }
        pdo_update($this->modulename . "_member", array('usernames' => $usernames, 'tel' => $tel), array('id' => $member['id']));
        $theme = pdo_fetch("SELECT xprice,title FROM " . tablename($this->modulename . "_hgoods") . " WHERE goods_id = '{$goods_id}'");
        $desc = $theme['title'];
        $fee = floatval($theme['xprice']);
        $result = $this->unifiedPay($member, $desc, $fee, $goods_id);
        die(json_encode($result));
    }


    private function unifiedPay($member, $desc, $fee, $goods_id)
    {
        global $_W;
        $system = $this->module['config'];
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $random = random(8);
        $orderno = $this->randorder();
        $trade_type = 'JSAPI';
        $thisappid = $_W['account']['key'];
        //Return $result = array("errcode" => 1, "errmsg" => $member['from_user']);

        $post = array('appid' => $system['appid'], 'mch_id' => $system['mchid'], 'nonce_str' => $random, 'body' => $desc, 'out_trade_no' => $orderno, 'total_fee' => $fee * 100, 'spbill_create_ip' => $system['ip'], 'notify_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/payment/wechat/pay.php', 'trade_type' => $trade_type, 'openid' => $member['from_user']);

        ksort($post);

        $string = $this->ToUrlParams($post);
        $string .= "&key={$system['apikey']}";
        $sign = md5($string);
        $post['sign'] = strtoupper($sign);
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($post),FILE_APPEND);


        $resp = $this->postXmlCurl($this->ToXml($post), $url);
        //Return $result = array("errcode" => 1, "errmsg" => $post['sign']);

        libxml_disable_entity_loader(true);
        $resp = json_decode(json_encode(simplexml_load_string($resp, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        //Return $result = array("errcode" => 1, "errmsg" => $resp['return_code']);
        if ($resp['result_code'] != 'SUCCESS') {
            //return array('errcode' => 1, 'errmsg' => $resp['return_code']);
            return array('errcode' => 1, 'errmsg' => $resp['return_msg']);
        } else {
            $orderid = $this->addOrder($member, $post, $goods_id);//返回订单号
            $params = $this->getWxPayJsParams($resp['prepay_id']);
            $result = array("errcode" => 0, "auth" => 0, "timeStamp" => $params['timeStamp'], "nonceStr" => $params['nonceStr'], "package" => $params['package'], "signType" => $params['signType'], "paySign" => $params['paySign'], "orderno" => $orderno, "orderid" => $orderid);
            return $result;
        }
    }

    private function addOrder($member, $post, $goods_id)//插入订单
    {
        global $_W;
        $fee = $post['total_fee'] / 100;
        $data = array('weid' => $_W['uniacid'], 'orderno' => $post['out_trade_no'], 'goods_id' => $goods_id, 'price' => $fee, 'memberid' => $member['id'], 'from_user' => $member['from_user'], 'nickname' => $member['nickname'], 'avatar' => $member['avatar'], 'tel' => $member['tel'], 'cengji' => 0, 'usernames' => $member['usernames'], 'createtime' => time());
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($data),FILE_APPEND);
        pdo_insert($this->modulename . "_order", $data);
        $orderid = pdo_insertid();
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($orderid),FILE_APPEND);
        return $orderid;
    }

    public function doMobileCheckJsPayResult()
    {
        global $_GPC, $_W;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        $orderno = $_GPC['orderno'];
        $orderid = $_GPC['orderid'];
        $order = pdo_fetch("SELECT orderno FROM " . tablename($this->modulename . "_order") . " WHERE id = '{$orderid}' ");
        $result = $this->dealpayresult($order['orderno']);
        die(json_encode($result));
    }

    private function dealpayresult($orderno)
    {
        global $_W;
        $result = array();
        if (empty($orderno)) {
            $result['errcode'] = 1;
            $result['errmsg'] = '订单号为空';
        } else {
            $checkresult = $this->checkWechatTranByOrderNo($orderno);
            if ($checkresult['errcode'] == 0) {
                $order = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_order") . " WHERE  weid = '{$_W['uniacid']}' and orderno ='{$orderno}'");
                if ($order['paystate'] == 0) {
                    $data = array('paystate' => 1, 'paytime' => time());
                    if ($order['state'] == 0) {
                        $data['state'] = 1;
                    }
                    pdo_update($this->modulename . "_order", $data, array('id' => $order['id']));
                    pdo_update($this->modulename . "_member", array('fans_type' => 1, 'createtime' => TIMESTAMP), array('weid' => $_W['uniacid'], 'from_user' => $order['from_user']));
                    $this->jiangli($order['from_user'], $order);//插入上级分佣金订单
                    //$system = $this->module['config'];
                    //if ($system['noticeopen'] == 1 && !empty($system['orderpayok'])) {//支付成功模版消息
                    //    $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('jiameng', array('goods_id' => $order['goods_id']));
                    //    $this->sendNotice($url, $order['openid'], $system['orderpayok'], $order);
                    //}
                }
                $url = $_W['siteroot'] . 'app/' . $this->createMobileUrl('jiameng', array('goods_id' => $order['goods_id']));//支付成功跳转
                $result['errcode'] = 0;
                $result['msg'] = '支付成功!';
                $result['url'] = $url;
            } else {
                $result['errcode'] = 1;
                $result['errmsg'] = $checkresult['errmsg'];
            }
        }
        return $result;
    }

    public function jiangli($openid, $order)
    {//奖励上级分销佣金
        global $_W;
        $cfg = $this->module['config'];
        $member = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE from_user = '{$openid}' and weid='{$_W['uniacid']}' order by id desc limit 1");
        $data = array(
            'weid' => $_W['uniacid'],
            'orderno' => $order['orderno'],
            'goods_id' => $order['goods_id'],
            'state' => 1,
            //'from_user' => $member['from_user'],
            //'nickname' => $member['nickname'],
            //'avatar' => $member['avatar'],
            //'tel'=>$member['tel'],
            //'cengji'=>0,
            'paystate' => 1,
            'paytime' => $order['paytime'],
            //'usernames'=>$member['usernames'],
            'createtime' => time()
        );


        if (!empty($member['helpid'])) {
            $sjmember = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE id = '{$member['helpid']}' and weid='{$_W['uniacid']}' order by id desc limit 1");
            if (!empty($cfg['level1'])) {
                $data['price'] = $order['price'] * $cfg['level1'] / 100;
            } else {
                $data['price'] = $cfg['glevel1'];
            }
            $data['memberid'] = $sjmember['id'];
            $data['nickname'] = $sjmember['nickname'];
            $data['avatar'] = $sjmember['avatar'];
            $data['from_user'] = $sjmember['from_user'];
            $data['cengji'] = 1;
            $data['msg'] = $member['nickname'] . '一级奖励';
            pdo_insert($this->modulename . "_order", $data);

            if (!empty($sjmember['helpid'])) {
                $hmember = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE id = '{$sjmember['helpid']}' and weid='{$_W['uniacid']}' order by id desc limit 1");
                if (!empty($cfg['level1'])) {
                    $data['price'] = $order['price'] * $cfg['level2'] / 100;
                } else {
                    $data['price'] = $cfg['glevel2'];
                }
                $data['memberid'] = $hmember['id'];
                $data['nickname'] = $hmember['nickname'];
                $data['avatar'] = $hmember['avatar'];
                $data['from_user'] = $hmember['from_user'];
                $data['cengji'] = 2;
                $data['msg'] = $member['nickname'] . '二级奖励';
                pdo_insert($this->modulename . "_order", $data);

                if (!empty($hmember['helpid'])) {
                    $smember = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE id = '{$hmember['helpid']}' and weid='{$_W['uniacid']}' order by id desc limit 1");
                    if (!empty($cfg['level1'])) {
                        $data['price'] = $order['price'] * $cfg['level3'] / 100;
                    } else {
                        $data['price'] = $cfg['glevel3'];
                    }
                    $data['memberid'] = $smember['id'];
                    $data['nickname'] = $smember['nickname'];
                    $data['avatar'] = $smember['avatar'];
                    $data['from_user'] = $smember['from_user'];
                    $data['cengji'] = 3;
                    $data['msg'] = $member['nickname'] . '三级奖励';
                    pdo_insert($this->modulename . "_order", $data);
                }
            }


        }


    }

    private function checkWechatTranByOrderNo($orderno)
    {
        global $_W;
        $system = $this->module['config'];
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $random = random(8);
        $post = array('appid' => $system['appid'], 'out_trade_no' => $orderno, 'nonce_str' => $random, 'mch_id' => $system['mchid']);
        ksort($post);
        $string = $this->ToUrlParams($post);
        $string .= "&key={$system['apikey']}";
        $sign = md5($string);
        $post['sign'] = strtoupper($sign);
        $resp = $this->postXmlCurl($this->ToXml($post), $url);
        libxml_disable_entity_loader(true);
        $resp = json_decode(json_encode(simplexml_load_string($resp, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if ($resp['return_code'] == 'SUCCESS') {
            if ($resp['result_code'] == 'SUCCESS') {
                if ($resp['trade_state'] == 'SUCCESS') {
                    return array('errcode' => 0, 'fee' => $resp['total_fee'] / 100);
                } else {
                    return array('errcode' => 1, 'errmsg' => '未支付:' . $resp['trade_state']);
                }
            } else {
                return array('errcode' => 1, 'errmsg' => '订单不存在' . $resp['err_code']);
            }
        } else {
            return array('errcode' => 1, 'errmsg' => '查询失败:' . $resp['return_msg']);
        }
    }

    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }


    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($useCert == true) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
        }
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($data),FILE_APPEND);
            return $data;
        }
    }

    private function ToXml($post)
    {
        $xml = "<xml>";
        foreach ($post as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($xml),FILE_APPEND);
        return $xml;
    }

    private function getWxPayJsParams($prepay_id)
    {
        global $_W;
        $system = $this->module['config'];
        $random = random(8);
        $post = array('appId' => $system['appid'], 'timeStamp' => time(), 'nonceStr' => $random, 'package' => "prepay_id=" . $prepay_id, 'signType' => 'MD5');
        ksort($post);
        $string = $this->ToUrlParams($post);
        $string .= "&key={$system['apikey']}";
        $sign = md5($string);
        $post['paySign'] = strtoupper($sign);
        return $post;
    }


    //微信支付结束


    public function doMobileLibpay()
    {
        global $_W, $_GPC;
        $cfg = $this->module['config'];
        $goods_id = intval($_GPC['goods_id']); //会员费用ID
        $usernames = $_GPC['usernames'];//姓名
        $tel = $_GPC['tel'];//电话

        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        $goods = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_hgoods") . " WHERE goods_id = '{$goods_id}' order by goods_id desc limit 1");

        pdo_update($this->modulename . "_member", array('usernames' => $usernames, 'tel' => $tel), array('weid' => $_W['uniacid'], 'from_user' => $_W['openid']));
        $fee = floatval($goods['xprice']);
        //生成订单号
        $tid = $this->randorder();
        $ordersn = $this->randorder();
        //生成订单号结束

        pdo_delete($this->modulename . "_order", array('weid' => $_W['uniacid'], 'memberid' => $member['id'], 'state' => 0));//删除未付款的订单
        $data = array(
            'weid' => $_W['uniacid'],
            'ddtype' => 0,
            'memberid' => $member['id'],
            'usernames' => $usernames,
            'tel' => $tel,
            'from_user' => $_W['openid'],
            'city' => $_GPC['city'],
            'address' => $_GPC['address'],
            'province' => $_GPC['province'],
            'city' => $_GPC['city'],
            'country' => $_GPC['country'],
            'goods_id' => $goods_id,
            'ordersn' => $ordersn,
            'nickname' => $member['nickname'],
            'avatar' => $member['avatar'],
            'price' => $fee,
            'createtime' => date("Y-m-d H:i:s"),
        );
        pdo_insert($this->modulename . "_order", $data);
        $orderid = pdo_insertid();

        $params = array(
            'tid' => $orderid,      //充值模块中的订单号，此号码用于业务模块中区分订单，交易的识别码
            'ordersn' => $ordersn,  //收银台中显示的订单号
            'title' => $goods['title'],          //收银台中显示的标题
            'fee' => $fee,      //收银台中显示需要支付的金额,只能大于 0
            //'user' => $_W['member']['uid'],     //付款用户, 付款的用户名(选填项)
        );
        $this->pay($params);

    }

    /**
     * 支付后触发这个方法
     * @param $params
     */
    public function payResult($params)
    {
        global $_W;
        load()->model('mc');
        $cfg = $this->module['config'];
        //一些业务代码
        $orderid = $params['tid'];
        $order = pdo_fetch("SELECT goods_id,price FROM " . tablename($this->modulename . "_order") . " WHERE id = '{$orderid}' ");
        if ($params['fee'] != $order['price']) {
            exit('用户支付的金额与订单金额不符合');
        }

        //一些业务代码
        //根据参数params中的result来判断支付是否成功
        if ($params['result'] == 'success' && $params['from'] == 'notify') {
            file_put_contents(IA_ROOT . "/addons/task_tiger/log.txt", "\n old:" . json_encode($params), FILE_APPEND);
            file_put_contents(IA_ROOT . "/addons/task_tiger/log.txt", "\n old:" . json_encode('111'), FILE_APPEND);

        }
        //因为支付完成通知有两种方式 notify，return,notify为后台通知,return为前台通知，需要给用户展示提示信息
        //return做为通知是不稳定的，用户很可能直接关闭页面，所以状态变更以notify为准
        //如果消息是用户直接返回（非通知），则提示一个付款成功
        if ($params['from'] == 'return') {
            if ($params['result'] == 'success') {
                //include $this->template('result');
                file_put_contents(IA_ROOT . "/addons/task_tiger/log.txt", "\n old:" . json_encode($params), FILE_APPEND);
                echo '支付成功';
            } else {
                message('支付失败！', '../../app/' . url('mc/home'), 'error');
            }
        }
    }

    public function gxorder($params)
    {
        global $_W;
        $orderid = $params['tid'];
        $order = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_order") . " WHERE id = '{$orderid}' ");
        if (!empty($order) && $order['state'] == 0) {
            if ($order['state'] == 0) {
                pdo_update($this->ordertable, array('state' => 1, 'paytime' => date("Y-m-d H:i:s")), array('id' => $orderid));
            } else {
                exit('订单已支付');
            }
        }
        exit('订单不存在或已支付');
    }

    function randorder()
    {//随机订单
        list($t1, $t2) = explode(' ', microtime());
        $basecode = (double)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        return date("YmdHis") . substr($basecode, 2, 10) . mt_rand(1000, 9999);
    }


    public function ismember($fans, $helpid)
    {//查找会员表里面有没有这个会员，没有插入数据，返回会员信息
        global $_W;
        $member = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and from_user = '{$fans['openid']}'");//当前粉丝信息
        if (!empty($helpid)) {
            $sjmember = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and id = '{$helpid}'");//上级信息
            $helpid = $sjmember['id'];
        } else {
            $helpid = 0;
        }

        $follow = $this->follow($fans['openid']);

        if (empty($member)) {
            if (empty($fans['openid'])) {
                echo '请在微信客户端打开！';
                exit;
            }
            $data = array(
                'weid' => $_W['uniacid'],
                'from_user' => $fans['openid'],
                'openid' => $fans['openid'],
                'unionid' => $fans['unionid'],
                'nickname' => $fans['nickname'],
                'helpid' => $helpid,
                'avatar' => $fans['headimgurl'],
                'sex' => $fans['sex'],
                'follow' => $follow,
                'province' => $fans['province'],
                'city' => $fans['city'],
                'createtime' => TIMESTAMP
            );
            pdo_insert($this->modulename . "_member", $data);
        } else {
            pdo_update($this->modulename . "_member", array('follow' => $follow), array('weid' => $_W['uniacid'], 'from_user' => $fans['openid']));
        }
        $member = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and from_user = '{$fans['openid']}'");
        Return $member;
    }

    public function check($member)
    {
        global $_W, $_GPC;
        if (empty($member['fans_type'])) {
            include $this->template('fx');
            exit;
        }
    }


    public function doMobileMember()
    {
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        if (!empty($member['helpid'])) {
            $sjmember = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and id = '{$member['helpid']}'");
            $sjname = $sjmember['nickname'];
        } else {
            $sjname = '平台';
        }
        $zyj = $this->getyongyin($member['id']);
        $ktxyj = $this->txyongyin($member['id']);


        include $this->template('member');
    }

    public function txyongyin($memberid)
    {//会员总计佣金
        global $_W;
        $m = pdo_fetch("SELECT sum(price) yj FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$memberid}' and cengji<>0 and paystate=1 and  txtype=0");
        //$hyyj=count($m);
        if (empty($m['yj'])) {
            $m['yj'] = '0.00';
        }
        return $m['yj'];
    }


    public function doMobileYpview()
    {//音频内容
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        $id = $_GPC['id'];
        $view = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_news") . " WHERE weid = '{$_W['uniacid']}' and id = '{$id}'");
        //echo $view['content'];
        //exit;


        if (empty($view['fftype'])) {//免费分类
            if (empty($view['nrtype'])) {//0 新闻
                include $this->template('view');
            } elseif ($view['nrtype'] == 1) {//1 音频
                include $this->template('ypview');
            } elseif ($view['nrtype'] == 2) {//2 视频
                include $this->template('view');
            } elseif ($view['nrtype'] == 3) {//3 外链
                include $this->template('view');
            } else {
                echo '非法访问';
                exit;
            }
        } elseif ($view['fftype'] == 1) {//收费分类
            if (empty($member['fans_type'])) {//收费会员
                include $this->template('fx');
                exit;
            } else {
                if (empty($view['nrtype'])) {//0 新闻
                    include $this->template('view');
                } elseif ($view['nrtype'] == 1) {//1 音频
                    include $this->template('ypview');
                } elseif ($view['nrtype'] == 2) {//2 视频
                    include $this->template('view');
                } elseif ($view['nrtype'] == 3) {//3 外链
                    include $this->template('view');
                } else {
                    echo '非法访问';
                    exit;
                }
            }
        } else {
            echo '非法访问';
            exit;
        }


    }


    public function doMobileDaili()
    {//我的代理
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束


        $count1 = 0;
        $count2 = 0;
        $count3 = 0;

        $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid='{$member['id']}'", array(), 'id');
        $count1 = count($fans1);
        if (empty($count1)) {
            $count1 = 0;
            $count2 = 0;
            $count3 = 0;
        }
        if (!empty($fans1)) {
            $fans2 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans1)) . ")", array(), 'id');
            $count2 = count($fans2);
            if (empty($count2)) {
                $count2 = 0;
            }
        }
        if (!empty($fans2)) {
            $fans3 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans2)) . ")", array(), 'id');
            $count3 = count($fans3);
            if (empty($count3)) {
                $count3 = 0;
            }
        }


        include $this->template('daili');
    }

    public function doMobileDllist()
    {

        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        $id = $member['id'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 50;

        $op = $_GPC['op'];

        if ($op == 1) {
            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid='{$id}' order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and helpid='{$id}'");

        } elseif ($op == 2) {

            $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid='{$id}'", array(), 'id');
            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans1)) . ") LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            //echo '<pre>';
            //print_r($list);
            //exit;
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans1)) . ")");
        } elseif ($op == 3) {

            $fans1 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid='{$id}' order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}", array(), 'id');
            $fans2 = pdo_fetchall("select id from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans1)) . ")", array(), 'id');

            if (!empty($fans2)) {
                $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans2)) . ") LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
                $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}' and helpid in (" . implode(',', array_keys($fans2)) . ")");
            }
        } else {

            $list = pdo_fetchall("select * from " . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' {$where} order by createtime desc LIMIT " . ($pindex - 1) * $psize . ",{$psize}");
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->modulename . '_member') . " where weid='{$_W['uniacid']}'");

        }

        include $this->template('dllist');

    }

    public function doMobileShouyi()
    {//我的收益
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束


        $lv1 = pdo_fetch("SELECT sum(price) yj FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$member['id']}' and cengji=1 and paystate=1");
        if (empty($lv1['yj'])) {
            $lv1['yj'] = '0.00';
        }
        $lv2 = pdo_fetch("SELECT sum(price) yj FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$member['id']}' and cengji=2 and paystate=1");
        if (empty($lv2['yj'])) {
            $lv2['yj'] = '0.00';
        }
        $lv3 = pdo_fetch("SELECT sum(price) yj FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$member['id']}' and cengji=3 and paystate=1");
        if (empty($lv3['yj'])) {
            $lv3['yj'] = '0.00';
        }


        $lv1 = $lv1['yj'];
        $lv2 = $lv2['yj'];
        $lv3 = $lv3['yj'];


        include $this->template('shouyi');
    }

    public function doMobileTxget()
    {
        global $_W, $_GPC;
        if (!$_W['isajax']) die(json_encode(array('success' => 0, 'msg' => '非法提交,只能通过网站提交')));
        $cfg = $this->module['config'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $cid = $_GPC['cid'];

        $hb = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and memberid='{$member['id']}' and id='{$cid}' and paystate=1 and txtype=0");
        if (!empty($hb)) {
            $pre = $hb['price'] * 100;
            if (empty($cfg['txtype1'])) {
                $msg = $this->post_qyfk($cfg, $hb['from_user'], $pre, '你提现的奖励');//企业零钱付款
                if ($msg['message'] == 'success') {
                    pdo_update($this->modulename . "_order", array('txtype' => 1, 'txtime' => TIMESTAMP), array('id' => $cid));
                    die(json_encode(array("success" => 1, "msg" => '提现成功,请到微信钱包查看！如果有多个，请隔5分钟左右在提现，以免提现失败！')));
                } else {
                    die(json_encode(array("success" => 0, "msg" => $msg['message'])));
                }
            } else {
                $result = pdo_update($this->modulename . "_order", array('txtype' => 2, 'txtime' => TIMESTAMP), array('id' => $cid));//提交审核中心
                if (!empty($result)) {
                    die(json_encode(array("success" => 1, "msg" => '提现成功，客服会在24小时之内审核，请耐心等待！')));
                } else {
                    die(json_encode(array("success" => 0, "msg" => '系统繁忙，请过一会在试！感谢您的支持！')));
                }
            }

        } else {
            die(json_encode(array("success" => 0, "msg" => '已经提现过了哦!不要重复提交!')));
        }

    }

    public function doMobileSzget()
    {
        global $_W, $_GPC;
        if (!$_W['isajax']) die(json_encode(array('success' => 0, 'msg' => '非法提交,只能通过网站提交')));
        $cfg = $this->module['config'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息

        $usernames = $_GPC['usernames'];
        $tel = $_GPC['tel'];
        $wechat = $_GPC['wechat'];

        $hb = pdo_fetch("SELECT * FROM " . tablename($this->modulename . "_member") . " WHERE weid = '{$_W['uniacid']}' and id='{$member['id']}'");

        if (empty($hb)) {
            die(json_encode(array("success" => 0, "msg" => '修改失败，系统繁忙！')));
        } else {
            $result = pdo_update($this->modulename . "_member", array('usernames' => $usernames, 'tel' => $tel, 'wechat' => $wechat), array('id' => $member['id']));

            if (!empty($result)) {
                die(json_encode(array("success" => 1, "msg" => '修改成功！')));
            } else {
                die(json_encode(array("success" => 0, "msg" => '系统繁忙，请过一会在试！感谢您的支持！')));
            }
        }

    }

    public function doMobileFztype()
    {//分组内容
        global $_W, $_GPC;
        $cfg = $this->module['config'];
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        $fztype = $_GPC['id'];//分组ID
        $fz = pdo_fetch('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and id='{$fztype}' limit 1");
        $list = pdo_fetchall("SELECT * FROM " . tablename($this->modulename . "_news") . " WHERE weid = '{$_W['uniacid']}' and type='{$fz['id']}' ORDER BY id desc");


        //echo '<pre>';
        //print_r($list);
        //exit;
        if (empty($fz['fftype'])) {//免费分类
            if (empty($fz['nrtype'])) {//0 新闻
                $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=0 and fftype=0 order by px desc limit 4");
                include $this->template('news');
            } elseif ($fz['nrtype'] == 1) {//1 音频
                $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=1 and fftype=0 order by px desc limit 4");

                include $this->template('ypnews');
            } elseif ($fz['nrtype'] == 2) {//2 视频
                $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and id='{$fztype}' and nrtype=2 and fftype=0  limit 4");
                include $this->template('news');
            } elseif ($fz['nrtype'] == 3) {//3 外链
                $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=3 and fftype=0  order by px desc limit 4");
                include $this->template('wlnews/index');
            } else {
                echo '非法访问';
                exit;
            }
        } elseif ($fz['fftype'] == 1) {//收费分类
            if (empty($member['fans_type'])) {//收费会员
                //$url=$_W['siteroot'].str_replace('./','app/',$this->createMobileurl('jiameng'));
                //header("location:".$url);
                include $this->template('fx');
                exit;
            } else {
                if (empty($fz['nrtype'])) {//0 新闻
                    $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=0 and fftype=1 order by px desc limit 4");
                    include $this->template('news');
                } elseif ($fz['nrtype'] == 1) {//1 音频
                    $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=1 and fftype=1  order by px desc limit 4");
                    include $this->template('ypnews');
                } elseif ($fz['nrtype'] == 2) {//2 视频
                    $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and id='{$fztype}' and nrtype=2 and fftype=1  limit 4");
                    include $this->template('news');
                } elseif ($fz['nrtype'] == 3) {//3 外链
                    $fzlist = pdo_fetchall('select * from ' . tablename($this->modulename . "_fztype") . " where weid='{$_W['uniacid']}' and nrtype=3 and fftype=1  order by px desc limit 4");
                    include $this->template('wlnews/index');
                } else {
                    echo '非法访问';
                    exit;
                }
            }
        } else {
            echo '非法访问';
            exit;
        }
        //echo '<pre>';
        //print_r($fz);
        //echo 'aaa';
        //exit;


    }

    public function doMobileEwm()
    {//
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        $pid = $_GPC['pid'];

        //$poster=pdo_fetch ( 'select * from ' . tablename ($this->modulename . "_poster" ) . " where weid='{$_W['uniacid']}' limit 1" );
        $poster = pdo_fetch('select * from ' . tablename($this->modulename . "_poster") . " where weid='{$_W['uniacid']}' and id='{$pid}' limit 1");
        if (empty($poster)) {
            echo '商家未设置二维码海报！';
            exit;
        }
        $img = $this->createPoster($fans, $poster, $member['from_user']);
        $picimg = tomedia($img);
        include $this->template('ewm');
    }

    public function doMobileEwmlist()
    {//
        global $_W, $_GPC;
        $cfg = $this->module['config'];
        $list = pdo_fetchall('select * from ' . tablename($this->modulename . "_poster") . " where weid='{$_W['uniacid']}' order by id desc");
//        echo '<pre>';
//        print_r($list);
//        exit;
        include $this->template('ewmlist');
    }

    public function doMobileMyorder()
    {//我的订单
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        include $this->template('myorder');
    }

    public function doMobileJfshop()
    {//积分商城
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束
        include $this->template('jfshop');
    }

    public function doMobileCaiwu()
    {//财务明细
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        $op = $_GPC['op'];//无 全部  1可提现  2已提现
        if ($op == 1) {
            $where = " and txtype=0 or txtype=2";//可提现
        } elseif ($op == 2) {
            $where = " and txtype=1";//已提现
        } else {
            $where = " and txtype=0 or txtype=2";//可提现
            $op = 1;
        }

        $list = pdo_fetchall("SELECT * FROM " . tablename($this->modulename . "_order") . " WHERE weid = '{$_W['uniacid']}' and paystate=1  and cengji>0 and memberid='{$member['id']}' {$where}  ORDER BY id desc");
        include $this->template('caiwu');
    }

    public function doMobileTjcp()
    {//推荐产品
        global $_W, $_GPC;

        include $this->template('tjcp');
    }

    public function doMobileSz()
    {//设置
        global $_W, $_GPC;
        //任务分享注册
        $helpid = $_GPC['helpid'];
        $fans = mc_oauth_userinfo();
        $member = $this->ismember($fans, $helpid);//当前粉丝信息
        $helpid = $member['id'];
        //结束

        include $this->template('sz');
    }


    /**
     * 获取客户资料
     * $access_token= account_weixin_token($_W['account']);
     * 当用户接到到一条模板消息，会给公共平台api发送一个xml文件【待处理】
     */
    private function sendtext($txt, $openid)
    {
        global $_W;
        $acid = $_W['account']['acid'];
        if (!$acid) {
            $acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account') . " WHERE uniacid=:uniacid ", array(':uniacid' => $_W['uniacid']));
        }
        $acc = WeAccount::create($acid);
        $data = $acc->sendCustomNotice(array('touser' => $openid, 'msgtype' => 'text', 'text' => array('content' => urlencode($txt))));
        return $data;
    }


    /**
     * @name    单发模式
     * @param    openid        粉丝编号
     * @param    tplmsgid    模版消息id
     * @param    data        数据包
     * @param    url        跳转地址
     */
    public function sendmsg($openid, $tplmsgid, $data = array(), $url = "")
    {
        global $_W;
        if (!empty($data)) {
            //记录存在 | 发送接口
            $account = WeAccount::create($_W['account']['acid']);
            //公号类型
            if ($_W['account']['level'] == 3) {
                //订阅号 | 客服消息
                //转换内容
                foreach ($tplmsg["data"] as $key => $value) {
                    $tplmsg["content"] = str_replace("{{" . $key . ".DATA}}", $value["value"], $tplmsg["content"]);
                }
                return $account->sendCustomNotice(
                    array(
                        "touser" => $openid,
                        "msgtype" => "text",
                        "text" => array(
                            "content" => urlencode($tplmsg["content"]),
                        ),
                    )
                );
            } elseif ($_W['account']['level'] == 4) {
                //服务号 | 模板消息
                //return $account->sendTplNotice($openid, $tplmsgid, $tplmsg['data'], $url);
                return $account->sendTplNotice($openid, $tplmsgid, $data, $url);
            }
        }
    }


    //现金红包接口
    function post_txhb($cfg, $openid, $dtotal_amount, $desc)
    {
        global $_W;
        //提现金额限制开始
        /*
       if(!empty($desc)){
         $fans = mc_fetch($_W['openid']);
         $dtotal=$dtotal_amount/100;
         //file_put_contents(IA_ROOT."/addons/tiger_jifenbao/log.txt","\n old:".json_encode($dtotal."||||".$desc."||||".$fans['credit2']),FILE_APPEND);

         if($dtotal>$fans['credit2']){
            $ret['code']=-1;
            $ret['dissuccess']=0;
            $ret['message']='余额不足';
            return $ret;
            exit;
         }
       }*/
        //提现金额限制结束
        $root = IA_ROOT . '/attachment/task_tiger/cert/' . $_W['uniacid'] . '/';
        $ret = array();
        $ret['code'] = 0;
        $ret['message'] = "success";
        //  return $ret;
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $pars = array();
        $pars['nonce_str'] = random(32);
        $pars['mch_billno'] = random(10) . date('Ymd') . random(3);
        $pars['mch_id'] = $cfg['mchid'];
        $pars['wxappid'] = $cfg['appid'];
        $pars['nick_name'] = $_W['account']['name'];
        $pars['send_name'] = $_W['account']['name'];
        $pars['re_openid'] = $openid;
        $pars['total_amount'] = $dtotal_amount;
        $pars['min_value'] = $dtotal_amount;
        $pars['max_value'] = $dtotal_amount;
        $pars['total_num'] = 1;
        $pars['wishing'] = '提现红包成功!';
        $pars['client_ip'] = $cfg['client_ip'];
        $pars['act_name'] = '兑换红包';
        $pars['remark'] = "来自" . $_W['account']['name'] . "的红包";

        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key={$cfg['apikey']}";
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        $extras = array();
        //$cert=json_decode($cfg['nbfwxpaypath']);

        $extras['CURLOPT_CAINFO'] = $root . 'rootca.pem';
        $extras['CURLOPT_SSLCERT'] = $root . 'apiclient_cert.pem';
        $extras['CURLOPT_SSLKEY'] = $root . 'apiclient_key.pem';

        load()->func('communication');
        $procResult = null;
        $resp = ihttp_request($url, $xml, $extras);
        if (is_error($resp)) {
            $procResult = $resp["message"];
            $ret['code'] = -1;
            $ret['message'] = $procResult;
            return $ret;
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
            $dom = new DOMDocument();
            if ($dom->loadXML($xml)) {
                $xpath = new DOMXPath($dom);
                $code = $xpath->evaluate('string(//xml/return_code)');
                $result = $xpath->evaluate('string(//xml/result_code)');
                if (strtolower($code) == 'success' && strtolower($result) == 'success') {
                    $ret['code'] = 0;
                    $ret['dissuccess'] = 1;
                    $ret['message'] = "success";
                    return $ret;

                } else {
                    $error = $xpath->evaluate('string(//xml/err_code_des)');
                    $ret['code'] = -2;
                    $ret['dissuccess'] = 0;
                    $ret['message'] = $error;
                    return $ret;
                }
            } else {
                $ret['code'] = -3;
                $ret['dissuccess'] = 0;
                $ret['message'] = "3error3";
                return $ret;
            }

        }
    }


    //企业零钱付款接口
    public function post_qyfk($cfg, $openid, $amount, $desc)
    {
        global $_W;
        //提现金额限制开始
        /*
       if(!empty($desc)){
         $fans = mc_fetch($_W['openid']);
         $dtotal=$amount/100;
         //file_put_contents(IA_ROOT."/addons/tiger_jifenbao/log.txt","\n old:".json_encode($dtotal."||||".$desc."||||".$fans['credit2']),FILE_APPEND);
         if($dtotal>$fans['credit2']){
            $ret['code']=-1;
            $ret['dissuccess']=0;
            $ret['message']='余额不足';
            return $ret;
            exit;
         }
       }*/
        //提现金额限制结束
        $root = IA_ROOT . '/attachment/task_tiger/cert/' . $_W['uniacid'] . '/';
        $ret = array();
        $ret['code'] = 0;
        $ret['message'] = "success";

        $ret['amount'] = $amount;
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $pars = array();
        $pars['mch_appid'] = $cfg['appid'];
        $pars['mchid'] = $cfg['mchid'];
        $pars['nonce_str'] = random(32);
        $pars['partner_trade_no'] = random(10) . date('Ymd') . random(3);
        $pars['openid'] = $openid;
        $pars['check_name'] = "NO_CHECK";
        $pars['amount'] = $amount;
        $pars['desc'] = "来自" . $_W['account']['name'] . "的提现";
        $pars['spbill_create_ip'] = $cfg['ip'];
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key={$cfg['apikey']}";
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        //$cert=json_decode($cfg['nbfwxpaypath']);
        $extras = array();
        $extras['CURLOPT_CAINFO'] = $root . 'rootca.pem';
        $extras['CURLOPT_SSLCERT'] = $root . 'apiclient_cert.pem';
        $extras['CURLOPT_SSLKEY'] = $root . 'apiclient_key.pem';


        load()->func('communication');
        $procResult = null;
        $resp = ihttp_request($url, $xml, $extras);
        if (is_error($resp)) {
            $procResult = $resp['message'];
            $ret['code'] = -1;
            $ret['dissuccess'] = 0;
            $ret['message'] = "-1:" . $procResult;
            return $ret;
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
            $dom = new DOMDocument();
            if ($dom->loadXML($xml)) {
                $xpath = new DOMXPath($dom);
                $code = $xpath->evaluate('string(//xml/return_code)');
                $result = $xpath->evaluate('string(//xml/result_code)');
                if (strtolower($code) == 'success' && strtolower($result) == 'success') {
                    $ret['code'] = 0;
                    $ret['dissuccess'] = 1;
                    $ret['message'] = "success";
                    return $ret;

                } else {
                    $error = $xpath->evaluate('string(//xml/err_code_des)');
                    $ret['code'] = -2;
                    $ret['dissuccess'] = 0;
                    $ret['message'] = "-2:" . $error;
                    return $ret;
                }
            } else {
                $ret['code'] = -3;
                $ret['dissuccess'] = 0;
                $ret['message'] = "error response";
                return $ret;
            }
        }

    }


    private $sceneid = 0;
    private $Qrcode = "/addons/task_tiger/qrcode/mposter#sid#.jpg";

    private function createPoster($fans, $poster, $openid)
    {
        global $_W;
        $bg = $poster['bg'];
        //$share = pdo_fetch('select * from '.tablename($this->modulename."_member")." where weid='{$_W['uniacid']}' and from_user='{$openid}' limit 1");
        $share = pdo_fetch('select * from ' . tablename($this->modulename . "_member") . " where weid='{$_W['uniacid']}' and pid='{$poster['id']}' and from_user='{$openid}' limit 1");

        if (empty($share)) {
            pdo_insert($this->modulename . "_member",
                array(
                    'uid' => $fans['uid'],
                    'nickname' => $fans['nickname'],
                    'avatar' => $fans['avatar'],
                    'createtime' => time(),
                    'helpid' => 0,
                    'pid' => $poster['id'],
                    'weid' => $_W['uniacid'],
                    'from_user' => $openid,
                    'openid' => $openid,
                    'unionid' => $fans['unionid'],
                    'sex' => $fans['sex'],
                    'follow' => 1
                ));
            $share['id'] = pdo_insertid();
            $share = pdo_fetch('select * from ' . tablename($this->modulename . "_member") . " where id='{$share['id']}'");
        } else pdo_update($this->modulename . "_member", array('updatetime' => time()), array('id' => $share['id']));


        $qrcode = str_replace('#sid#', $share['id'], IA_ROOT . $this->Qrcode);
        $data = json_decode(str_replace('&quot;', "'", $poster['data']), true);
        include 'func.php';
        set_time_limit(0);
        @ini_set('memory_limit', '256M');
        $size = getimagesize(tomedia($bg));
        $target = imagecreatetruecolor($size[0], $size[1]);
        $bg = imagecreates(tomedia($bg));
        imagecopy($target, $bg, 0, 0, 0, 0, $size[0], $size[1]);
        imagedestroy($bg);


        foreach ($data as $value) {
            $value = trimPx($value);
            if ($value['type'] == 'qr') {
                $url = $this->getQR($fans, $poster, $share['id']);
                //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($url),FILE_APPEND);
                if (!empty($url)) {
                    $img = IA_ROOT . "/addons/task_tiger/qrcode/temp_qrcode.png";
                    include "phpqrcode.php";
                    $errorCorrectionLevel = "L";
                    $matrixPointSize = "4";
                    QRcode::png($url, $img, $errorCorrectionLevel, $matrixPointSize, 2);
                    mergeImage($target, $img, array('left' => $value['left'], 'top' => $value['top'], 'width' => $value['width'], 'height' => $value['height']));
                    @unlink($img);
                }
            } elseif ($value['type'] == 'img') {
                $img = saveImage($fans['avatar']);
                mergeImage($target, $img, array('left' => $value['left'], 'top' => $value['top'], 'width' => $value['width'], 'height' => $value['height']));
                @unlink($img);
            } elseif ($value['type'] == 'name') mergeText($this->modulename, $target, $fans['nickname'], array('size' => $value['size'], 'color' => $value['color'], 'left' => $value['left'], 'top' => $value['top']), $poster);
        }
        imagejpeg($target, $qrcode);
        imagedestroy($target);
        return $qrcode;
    }

    private function getQR($fans, $poster, $sid)
    {
        global $_W;
        $pid = $poster['id'];

        //file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($this->rule),FILE_APPEND);
        // file_put_contents(IA_ROOT."/addons/task_tiger/log.txt","\n old:".json_encode($this->sceneid),FILE_APPEND);
        if (empty($this->sceneid)) {

            $share = pdo_fetch('select * from ' . tablename($this->modulename . "_member") . " where id='{$sid}'");

            if (!empty($share['url'])) {
                $out = false;
                if ($poster['rtype']) {
                    $qrcode = pdo_fetch('select * from ' . tablename('qrcode')
                        . " where uniacid='{$_W['uniacid']}' and qrcid='{$share['id']}' "
                        . " and name='" . $this->modulename . "' and ticket='{$share['ticketid']}' and url='{$share['url']}'");
                    if ($qrcode['createtime'] + $qrcode['expire'] < time()) {
                        pdo_delete('qrcode', array('id' => $qrcode['id']));
                        $out = true;
                    }
                }
                if (!$out) {
                    $this->sceneid = $share['id'];
                    return $share['url'];
                }
            }


            $this->sceneid = $share['id'];
            //if (empty($this->sceneid)) $this->sceneid = 50000001;
            //else $this->sceneid++;
            $barcode['action_info']['scene']['scene_id'] = $this->sceneid;

            load()->model('account');
            $acid = pdo_fetchcolumn('select acid from ' . tablename('account') . " where uniacid={$_W['uniacid']}");
            $uniacccount = WeAccount::create($acid);
            $time = 0;
            if ($poster['rtype']) {
                $barcode['action_name'] = 'QR_SCENE';
                $barcode['expire_seconds'] = 30 * 24 * 3600;
                $res = $uniacccount->barCodeCreateDisposable($barcode);
                $time = $barcode['expire_seconds'];
            } else {
                $barcode['action_name'] = 'QR_LIMIT_SCENE';
                $res = $uniacccount->barCodeCreateFixed($barcode);
            }
            //$rid = $this->rule;
            $rid = $poster['rid'];
            $sql = "SELECT * FROM " . tablename('rule_keyword') . " WHERE `rid`=:rid LIMIT 1";
            $row = pdo_fetch($sql, array(':rid' => $rid));

            pdo_insert('qrcode',
                array('uniacid' => $_W['uniacid'],
                    'acid' => $acid,
                    'qrcid' => $this->sceneid,
                    'name' => $this->modulename,
                    'keyword' => $row['content'],
                    'model' => 1,
                    'ticket' => $res['ticket'],
                    'expire' => $time,
                    'createtime' => time(),
                    'status' => 1,
                    'url' => $res['url']
                )
            );

            pdo_update($this->modulename . "_member", array('sceneid' => $this->sceneid, 'ticketid' => $res['ticket'], 'url' => $res['url'], 'nickname' => $fans['nickname'], 'avatar' => $fans['avatar']), array('id' => $sid));
            return $res['url'];
        }

    }


    //判断用户是否关注
    public function follow($openid)
    {
        global $_W;
        $access_token = $this->getAccessToken();
        //$openid=$_W['openid'];
        //$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $ret = ihttp_get($url);
        $auth = @json_decode($ret['content'], true);
        Return $subscribe = $auth['subscribe'];
    }

    private function getAccessToken()
    {
        global $_W;
        load()->model('account');
        $acid = $_W['acid'];
        if (empty($acid)) {
            $acid = $_W['uniacid'];
        }
        $account = WeAccount::create($acid);
        //$token = $account->fetch_available_token();
        $token = $account->getAccessToken();
        return $token;
    }

    private function getAccessToken2($acid)
    {
        $account = WeAccount::create($acid);
        //$token = $account->fetch_available_token();
        $token = $account->getAccessToken();
        return $token;
    }


}