<?php
/**
 * 图片处理
 * @author Administrator
 *
 */
class ImgGD{
    /**
     * 创建分享图片
     */
    public function createShareImg($data){
        // 1 获取背景图尺寸
        list($bg_w,$bg_h) = getimagesize($data['bg']);
        // 2 创建画图
        $img = @imagecreatetruecolor($bg_w,$bg_h);
        // 3 填充画布背景颜色
        $img_bg_color = imagecolorallocate($img,255,255,255);
        imagefill($img,0,0,$img_bg_color);
        // 4 将背景图填充到画布
        $bg_img = $this->getImgReource($data['bg']);
        imagecopyresized($img,$bg_img,0,0,0,0,$bg_w,$bg_h,$bg_w,$bg_h);
        // 5 填空用户二维码
        $qrcode = $this->getImgReource($data['qrcode']);
        // 计算用户二维码所在x轴位置
        list($qr_w,$qr_h) = getimagesize($data['qrcode']);
        $qrcode_des_x = ceil(($bg_w - $qr_w)/2);
        imagecopyresized($img,$qrcode,$qrcode_des_x,513,0,0,$qr_w,$qr_h,$qr_w,$qr_h);
        // 6 填充用户信息
        if(preg_match('/^http:\/\//',$data['user'])){
            $data['user'] = $this->getNetImgExt($data['user']);
        }
        $user_img_path = $this->thumbImg($data['user']);
        $user_img = $this->getImgReource($user_img_path);
        list($user_w,$user_h) = getimagesize($user_img_path);
        
        imagecopyresized($img,$user_img,13,20,0,0,$user_w,$user_h,$user_w,$user_h);
        // 填空用户名
        $user_name = $data['user_name'];
        $font_color = ImageColorAllocate($img,253,254,255); //字体颜色
        $font_ttf = "../ziti/HYTangMeiRenJ-2.ttf";
        imagettftext($img,23,0,90,50,$font_color,$font_ttf,$user_name);
        // 7 设置提示语
        $tip_text = "邀请你立即关注";
        imagettftext($img,17,0,90,80,$font_color,$font_ttf,$tip_text);
        // 8 输出图片
        header("Content-type: image/png");
        imagepng($img);
        // 9 释放空间
        imagedestroy($img);
        imagedestroy($bg_img);
        imagedestroy($qrcode);
        imagedestory($user_img);
    }
    /**
     * 获取图像文件资源
     * @param string $file
     * @return resource
     */
    protected function getImgReource($file){
        if(preg_match('/^http:\/\//',$file)){
            //网络图片
            $file_ext = $this->getNetImgExt($file);
        }else{
            //本地图片
            $file_ext = pathinfo($file,PATHINFO_EXTENSION);
        }
        switch ($file_ext){
            case 'jpg':
            case 'jpeg':
                $img_reources = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $img_reources = @imagecreatefrompng($file);
                break;
            case 'gif':
                $img_reources = @imagecreatefromgif($file);
                break;
        }
        return  $img_reources;
    }
    /**
     * 缩放图片
     * @param string $img 
     * @param string $file
     * @param number $th_w
     * @param number $th_h
     * @return boolean|string;
     */
    protected function thumbImg($img,$file='./',$th_w=62,$th_h=62){
        //给图像加大1像素的边框
        $new_th_h = $th_h;
        $new_th_w = $th_w;
        // 获取大图资源及图像大小
        list($max_w,$max_h) = getimagesize($img);
        if($max_w < $th_w || $max_h < $th_h) return $img;
        $max_img = $this->getImgReource($img);
        //新建真色彩画布
        
        $min_img = @imagecreatetruecolor($new_th_w,$new_th_h);
        $bg_color = ImageColorAllocate($min_img,255,255,255);
        imagefill($min_img,0,0,$bg_color);
        imagesavealpha($min_img,true);
        imagecolortransparent($min_img,$bg_color); 
        imagecopyresampled($min_img,$max_img,0,0,0,0,$th_w,$th_h,$max_w,$max_h);
        //输出图像到文件
        $min_img_path = $file . 'thunm_'.time().'.png';
        imagepng($min_img,$min_img_path);
        if(!is_file($min_img_path)){
            return false;
        }
        //释放空间
        imagedestroy($max_img);
        imagedestroy($min_img);
        return $min_img_path;
    }
    /**
     * 获取网络资源图片类型
     * @param unknown $url
     * @return boolean|unknown
     */
    protected function getNetImgExt($url){
        $curl = curl_init(); //初始化curl
        curl_setopt($curl,CURLOPT_URL,$url); //设置请求连接
        $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36";
        curl_setopt($curl,CURLOPT_USERAGENT,$user_agent); //设置请求头
        curl_setopt($curl,CURLOPT_AUTOREFERER,true); //重定向时是否自动设置referer头信息
        curl_setopt($curl,CURLOPT_HEADER,false); //是否启动将头文件信息作为文件流输出
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true); //将获取到的文件以字符串信息返回
        $response = curl_exec($curl); //执行url请求
        $response_info = curl_getinfo($curl); //获取请求信息
        curl_close($curl);
        if($response_info['http_code'] != 200){
            return  false;
        }
        $type = explode('/',$response_info['content_type']);
        $file_path = "./";
        $file_name = $file_path . 'user_'.time().rand(100,999).'.'.$type[1];
        file_put_contents($file_name ,$response);
        return $file_name;
    }
    
}
//调用
$data = [
    'bg' => '../img/bg.jpg',
    'qrcode' => '../img/qrcode.png',
    'user' => 'http://thirdwx.qlogo.cn/mmopen/vi_32/y5P21NBFY3c2jMPSP6DMjiaCUkr6qgl2p9Q3ystFe6OpKIqz9sJQkH8vlyThSuyMibeDDSmHfsF9YRpxxIB1G7Fg/132',
    'user_name' => '我的名字'
];
$gd_img = new ImgGD();
$gd_img->createShareImg($data);