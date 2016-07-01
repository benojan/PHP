<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller
{
	private function findThei($where, $hans)
	{
		$dict = M('dict');
        $order = array('concat(sin,yvn,diao)'=>'asc', 'faechieh' => 'asc');
    	$arr = $dict->where($where)->order($order)->select();
    	foreach($arr as $key => $value)
        {
            switch($value['diao']) {
    			case '1': $arr[$key]['diao'] = '33'; break;
    			case '2': $arr[$key]['diao'] = '31'; break;
    			case '3': $arr[$key]['diao'] = '42'; break;
    			case '4': $arr[$key]['diao'] = '31'; break;
    			case '5': $arr[$key]['diao'] = '55'; break;
    			case '6': $arr[$key]['diao'] = '11'; break;
    			case '7': $arr[$key]['diao'] = '5'; break;
    			case '8': $arr[$key]['diao'] = '1'; break;
    		}
        }
        return $arr;
	}

	private function findPihwak($where)
	{
		$dict = M('stroke');
    	$arr = $dict->where($where)->find();

    	$length = strlen($arr['stroke']);
    	$pihwaks = '';
    	for($index=0; $index < $length; ++$index) {
    		switch($arr['stroke'][$index]) {
    			case '1': $pihwaks .= '一'; break;
    			case '2': $pihwaks .= '丨'; break;
    			case '3': $pihwaks .= 'ノ'; break;
    			case '4': $pihwaks .= '丶'; break;
    			case '5': $pihwaks .= 'フ'; break;
    		}
    	}
    	$pihwaks .= ' ('. $arr['stroke'].')';
        return $pihwaks;
	}

	private function findKhonghi($where)
	{
		$dict = M('khdict');
    	$arr = $dict->where($where)->find();
        $arr['neiyung'] = str_replace("\t又", '<br/>又', $arr['neiyung']);
        $arr['neiyung'] = preg_replace('/[\x{4e00}-\x{9fcc}\x{3400}-\x{4db5}\x{f900}-\x{fad9}\x{20000}-\x{2a6d6}\x{2a700}-\x{2b734}\x{2b740}-\x{2b81d}\x{2b820}-\x{2cea1}]{2}切/u', '<span style="color:red;">${0}</span>', $arr['neiyung']);
        return $arr;
	}

	private function findKuongyvn($where)
	{
		$dict = M('kdict');
    	$arr = $dict->where($where)->select();
    	foreach($arr as $key => $value)
    	{
    		//$arr[$key]['hanzy'] = split(' ', $value['hanzy']);
    		preg_match_all('/[^\[\]\|\(\)\!\*\?（）]/u', $value['hanzy'], $tmp_array);
    		$arr[$key]['hanzy'] = $tmp_array[0];
	    	$where = array();
	    	$where['faechieh'] = $value['fanchiah'];
	    	$res = $this->findThei($where);
	    	$phinin = array();
	    	foreach ($res as $key1 => $value1) {
	    		$in = $value1['sin'] . $value1['yvn'];
	    		if(!in_array($in, $phinin))
	    		{
	    			array_push($phinin, $in);
	    		}
	    	}
	    	$arr[$key]['phinin'] = $phinin;
    	}
        return $arr;
	}

    public function index()
    {
        //if( !$this->check() ) return;

        $hanzy =  I('get.hanzy', '');
        $hanzy =  trim($hanzy);
        $hanzy = preg_replace("/ +/", " ", $hanzy);
    	$this  -> assign( 'hanzy', $hanzy );

        $arr  = array();
        $khdict = array();
        $kdict = array();
        $tdict = array();
        $sdict = array();
        $ksize = count($arr);
		$khsize = count($khdict);
        $tsize = count($tdict);
        $ssize = count($sdict);
        if($hanzy == '') { //为空，不操作
            $this->display();
            return;
        }

        //查询台州音
        //判断输入的成分
        if(preg_match_all("/[\x{4e00}-\x{9fcc}\x{3400}-\x{4db5}\x{f900}-\x{fad9}\x{20000}-\x{2a6d6}\x{2a700}-\x{2b734}\x{2b740}-\x{2b81d}\x{2b820}-\x{2cea1}]/u", $hanzy, $match) )
        {
            //匹配到多个汉字

            $hans = $match[0];
            $hans = array_unique($hans);//去重

            //逐个查询
            foreach($hans as $key => $han)
            {
                // echo $han . "<br>";
                // 处理台州字典
                $where['zy'] = $han;
                $where['cin'] = $han;
                $where['_logic'] = 'or';
                $tmp_tdict = $this->findThei($where);

                
                // 添加笔画结果
                $where = array();
                $where['hanzy'] = $han;
                $stroke = $this->findPihwak($where);
                foreach($tmp_tdict as $key => $value)
                {
                    //为一个汉字查询到的多个读音，添加笔画
                    $tmp_tdict[$key]['stroke'] = $stroke;
                }

                $tsize += count($tmp_tdict);
                $tdict = array_merge($tdict, $tmp_tdict);
            }

            if(count($match[0]) == 1)
            {
                //处理康熙字典
                $where = array();
                $where['hanzy'] = array('like', $match[0]);
                $where['_logic'] = 'or';
                $khdict = $this->findKhonghi($where);
                $khsize = count($khdict);
            }

            //处理广韵
            $where = array();
            foreach($match[0] as $key => $value)
            {
                $match[0][$key] = '%' . $value . '%'; // like应该是%***%格式
            }
            $where['hanzy'] = array('like', $match[0]);
            $kdict = $this->findKuongyvn($where);
            $ksize = count($kdict);

            //处理反切
            if(count($match[0]) == 2 && mb_strlen($hanzy, 'UTF-8') == 2)
            {
                $where = array();
                $where['fanchiah'] = $hanzy;
                $kdict2 = $this->findKuongyvn($where);
                $ksize2 = count($kdict2);
                $kdict = array_merge($kdict, $kdict2);
            }
        }

        if(preg_match_all("/[a-zA-Z]+(33|42|55|31|11|1|2|3|4|5|6|7|8)/", $hanzy, $match))
        {
        	// 匹配到sin1, dong4或sin33, dong42拼音格式
        	// $match[0]为{sin1,dong4,sin33,dong42}
        	// $match[1]为{1,4,33,42}

        	foreach($match[0] as $key => $value)
        	{
                // 转换声调
        		switch($match[1][$key])
        		{
        			case '33':
        				$match[0][$key] = substr($match[0][$key],0,-2) . '1';
        				break;
        			case '42':
        				$match[0][$key] = substr($match[0][$key],0,-2) . '3';
        				break;
        			case '55':
        				$match[0][$key] = substr($match[0][$key],0,-2) . '5';
        				break;
        			case '31':
        				$match[0][$key] = substr($match[0][$key],0,-2) . '2';
        				$match[0][count($match[0])+1] = substr($match[0][$key],0,-2) . '4';
        				break;
        			case '11':
        				$match[0][$key] = substr($match[0][$key],0,-2) . '6';
        				break;
        		}
        	}

            //查询此拼音的汉字
        	$where['concat(sin, yvn, diao)'] = array("in", $match[0]);
	        $tmp_tdict = $this->findThei($where);
            $where = array();
            foreach($tmp_tdict as $key => $value)
            {
                $where['hanzy'] = $value['zy'];
                $stroke = $this->findPihwak($where);
                $tmp_tdict[$key]['stroke'] = $stroke;
            }
	        $tsize += count($tmp_tdict);
            $tdict += $tmp_tdict;
        }

        if(preg_match_all("/([a-zA-Z]+)([ \t\x{4e00}-\x{9fa5}]|$)/u", $hanzy, $match))
        {
        	foreach($match[1] as $key => $value)
        	{
        		$match[1][$key] = trim($value);
        	}
			$where['concat(sin, yvn)'] = array("in", $match[1]);
	        $tmp_tdict = $this->findThei($where);
            $where = array();
            foreach($tmp_tdict as $key => $value)
            {
                $where['hanzy'] = $value['zy'];
                $stroke = $this->findPihwak($where);
                $tmp_tdict[$key]['stroke'] = $stroke;
            }
            $tsize += count($tmp_tdict);
            $tdict = array_merge($tdict, $tmp_tdict);
        }

        $this -> assign( 'hanzy' , $hanzy  );
        $this -> assign( 'tdict' , $tdict  );
        $this -> assign( 'khdict', $khdict );
		$this -> assign( 'kdict' , $kdict  );
        $this -> assign( 'tsize' , $tsize  );
        $this -> assign( 'khsize', $khsize );
		$this -> assign( 'ksize' , $ksize  );

    	$this -> display();
    }

    public function login()
    {
        $this->assign('title','登录系统');
        $this->display();
    }
    
    public function check()
    {
        $id = cookie('id');
        $user = cookie('user');
        $pass = cookie('pass');

        $check = M("User");
        $where['id'] = $id;
        $where['user'] = $user;
        $where['password'] = $pass;

        $data = $check->where($where)->find();
        
        if($data == null) {
            $this->success('请重新登录，即将跳转到登录界面……', U('Index/login'));
            return false;
        }
        return true;
    }

    public function cookie()
    {
        $user = I('post.user');
        $pass = I('post.pass');
        $check = M("User");
        $where['user'] = $user;
        $where['password'] = $pass;

        $data = $check->where($where)->find();
        if($data == null) {
            $this->success('登录失败，即将返回登录界面……', U('Index/login'));
        }
        else {
            cookie('id', $data['id'], 3600*24*30);
            cookie("user", $user, 3600*24*30);
            cookie("pass", $pass, 3600*24*30);

            $this->success('登录成功，即将跳转到首页……', U('Index/index'));
        } 
    }

    public function edit()
    {
        if( !$this->check() ) return;

        $id = I('get.id'); //basewordid
        $data = M('baseword');
        $where['id'] = $id;
        $result = $data->where($where)->select();
        $result = $result[0];
        $bid = $result['bigclassid'];
        $sid = $result['smallclassid'];
        $this->assign('bword', $result);

        //查询大类名
        $data = M('bigclass');
        $where['id'] = $bid;
        $result = $data->where($where)->select();
        $this->assign('bid', $result[0]['id']);
        $this->assign('bname', $result[0]['name']);

        //查询小类名
        $data = M('smallclass');
        $where['id'] = $sid;
        $result = $data->where($where)->select();
        $this->assign('sid', $result[0]['id']);
        $this->assign('sname', $result[0]['name']);

        //查询当前分类下的用词
        $data = M('word');
        $where = array();
        $where['basewordid'] = $id;
        $result = $data->where($where)->select();
        foreach($result as $key => $value)
        {
            $result[$key]['linhei']=($value['linhei'])?'checked':'';
            $result[$key]['thianthei']=($value['thianthei'])?'checked':'';
            $result[$key]['sanmen']=($value['sanmen'])?'checked':'';
            $result[$key]['xiankv']=($value['xiankv'])?'checked':'';
            $result[$key]['unlhin']=($value['unlhin'])?'checked':'';
            $result[$key]['niukwan']=($value['niukwan'])?'checked':'';
            $result[$key]['zykhv']=($value['zykhv'])?'checked':'';
        }
        $this->assign('word', $result);

        //显示
        $this->display();
    }

    public function update()
    {
        if( !$this->check() ) return;

        $id = I('post.id');
        $word = I('post.word');
        $phinin = I('post.phinin');
        $linhei = I('post.linhei');
        $thianthei = I('post.thianthei');
        $sanmen = I('post.sanmen');
        $xiankv = I('post.xiankv');
        $unlhin = I('post.unlhin');
        $niukwan = I('post.niukwan');
        $zykhv = I('post.zykhv');

        $data = M('word');
        $update['userid'] = cookie('id');
        $update['word']   = $word;
        $update['phinin'] = $phinin;
        $update['linhei'] = $linhei;
        $update['thianthei'] = $thianthei;
        $update['sanmen'] = $sanmen;
        $update['xiankv'] = $xiankv;
        $update['unlhin'] = $unlhin;
        $update['niukwan'] = $niukwan;
        $update['zykhv'] = $zykhv;
        $where['id'] = $id;
        $result = $data->where($where)->save($update);
        echo $result;
    }

    public function insert()
    {
        if( !$this->check() ) return;

        $db = M('word');
        $id = I('post.id');
        $data = array(
            'id' => NULL,
            'word' => '',
            'phinin' => '',
            'basewordid' => $id,
            'userid' => NULL,
            'linhei' => 0,
            'thianthei' => 0,
            'sanmen' => 0,
            'xiankv' => 0,
            'unlhin' => 0,
            'niukwan' => 0,
            'zykhv' => 0,
        );
        $db->add($data);
        $result = $db->order('id desc')->limit(1)->select();
        echo $result[0]['id'];
    }

    public function delete()
    {
        if( !$this->check() ) return;

        $id = I('post.id');
        $data = M('word');
        $where['id'] = $id;
        $result = $data->where($where)->delete();
        echo $result;
    }

	public function _empty()
    {
		echo "Can't Find " . ACTION_NAME . ' Page';
	}
}
?>
