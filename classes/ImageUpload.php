<?php
class ImageUpload {
    private $upload_dir = '/var/www/html/kreen/uploads/avatars';
    private $max_size = 5242880; // 5MB
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    public function __construct() {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    public function upload($file, $user_id) {
        if (!isset($file['tmp_name'])) {
            return ['success' => false, 'error' => 'ملف غير صحيح'];
        }
        
        // التحقق من الحجم
        if ($file['size'] > $this->max_size) {
            return ['success' => false, 'error' => 'الملف كبير جداً (حد أقصى 5MB)'];
        }
        
        // التحقق من نوع الملف
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $this->allowed_types)) {
            return ['success' => false, 'error' => 'نوع الملف غير مسموح'];
        }
        
        // التحقق من أن الملف صورة فعلية
        $info = getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['success' => false, 'error' => 'الملف ليس صورة صحيحة'];
        }
        
        // حذف الصور القديمة
        $this->deleteOldAvatars($user_id);
        
        // إنشاء اسم الملف
        $filename = "avatar_{$user_id}_" . time() . ".jpg";
        $filepath = $this->upload_dir . '/' . $filename;
        
        try {
            $this->compressImage($file['tmp_name'], $filepath);
            return ['success' => true, 'path' => '/kreen/uploads/avatars/' . $filename];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function compressImage($source, $dest) {
        $info = getimagesize($source);
        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'];
        
        if ($mime === 'image/jpeg') $img = imagecreatefromjpeg($source);
        elseif ($mime === 'image/png') $img = imagecreatefrompng($source);
        elseif ($mime === 'image/gif') $img = imagecreatefromgif($source);
        else throw new Exception('صيغة غير مدعومة');
        
        // تحديد الحجم (400px max)
        $max = 400;
        if ($width > $max) {
            $new_width = $max;
            $new_height = intval($height * ($max / $width));
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        $new_img = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagejpeg($new_img, $dest, 85);
        imagedestroy($img);
        imagedestroy($new_img);
    }
    
    public function deleteOldAvatars($user_id) {
        foreach (glob($this->upload_dir . "/avatar_{$user_id}_*") as $file) {
            if (is_file($file)) unlink($file);
        }
    }
    
    public function getAvatar($user_id) {
        $files = glob($this->upload_dir . "/avatar_{$user_id}_*");
        return !empty($files) ? '/kreen/uploads/avatars/' . basename(end($files)) : '/kreen/images/default-avatar.png';
    }
}
