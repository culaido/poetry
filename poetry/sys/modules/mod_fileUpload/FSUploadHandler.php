<?php
require_once MODULE_PATH . '/mod_fileUpload/UploadHandler.php';
class FSUploadHandler extends UploadHandler {
    protected $pageId;
    protected $oriFileName;
    protected $DELETE_URL = '/ajax/sys.modules.mod_fileUpload/mod_fileUpload.delete/';

    protected function initialize() {
        $this->pageId = args::get('pageId', 'string');
        switch ($this->get_server_var('REQUEST_METHOD')) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get(false);
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post();
                break;
            // case 'DELETE':
            //     $this->delete();
            //     break;
            default:
                $this->header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function get_user_id() {
    	return $this->pageId;
    }

    protected function get_file_size($file_path, $clear_stat_cache = false) {
        if ($clear_stat_cache) {
            // clearstatcache(true, $file_path); // php 5.2 does not support
            clearstatcache();
        }

        // fix img get filesize warring
        if (!is_file($file_path)) return 0;

        return $this->fix_integer_overflow(filesize($file_path));

    }

    protected function get_upload_path($file_name = null, $version = null) {
        $file_name = $file_name ? $file_name : '';
        $version_path = empty($version) ? '' : $version;
        $folderPath = $this->options['upload_dir'] . $this->get_user_path() . $version_path;
        if (!is_dir($folderPath)) {
            mkdir($folderPath, $this->options['mkdir_mode'], true);
        }
        return $folderPath . $file_name;
    }

    protected function get_final_path($file_name = null, $version = null) {
        $file_name = $file_name ? $file_name : '';

        if (!empty($file_name)) {
            $ext =  end(explode(".", $file_name));
            $file_name = md5(rand()) . "." . $ext;
        }

        $version_path = empty($version) ? '' : $version;
        $folderPath = $this->options['final_dir'] . $this->get_user_path(true) . $version_path;
        if (!is_dir($folderPath)) {
            mkdir($folderPath, $this->options['mkdir_mode'], true);
        }

        while(!empty($file_name) && is_file($folderPath . $file_name)) {
            $file_name = md5(rand()) . $ext;
        }

        return $folderPath . $file_name;
    }


    protected function get_download_url($file_name, $version = null) {
        if ($this->options['download_via_php']) {
            $url = $this->options['script_url']
                .$this->get_query_separator($this->options['script_url'])
                .'file='.rawurlencode($file_name);
            if ($version) {
                $url .= '&version='.rawurlencode($version);
            }
            return $url.'&download=1';
        }
        $version_path = empty($version) ? '' : rawurlencode($version).'/';
        return $this->options['upload_url'].$this->get_user_path(true)
            .$version_path.rawurlencode($file_name);
    }

    protected function get_user_path($flag = false) {
        if ($flag && $this->options['user_dirs']) {
            return $this->get_user_id() . '/';
        }
        return '';
    }


    protected function create_scaled_image ($file_path, $version, $options) {
		$file_name = substr($file_path, strrpos($file_path, '/') + 1);
        if (!empty($version)) {
            $version_dir = $this->get_final_path(null, $version);
            if (!is_dir($version_dir)) {
                @mkdir($version_dir, $this->options['mkdir_mode'], true);
            }
            $new_file_path = $version_dir . DIRECTORY_SEPARATOR . $file_name;
        } else {
            $new_file_path = $file_path;
        }
        if (!function_exists('getimagesize')) {
            error_log('Function not found: getimagesize');
            return false;
        }
        list($img_width, $img_height) = @getimagesize($file_path);
        if (!$img_width || !$img_height) {
            return false;
        }
        $max_width  = $options['max_width'];
        $max_height = $options['max_height'];
        $quality    = isset($options['quality']) ? $options['quality'] : 85; // default 85%

        // no resize if too small
        // $scale = min(
        //     $max_width / $img_width,
        //     $max_height / $img_height
        // );
        // if ($scale >= 1) {
        //     if ($file_path !== $new_file_path) {
        //         return copy($file_path, $new_file_path);
        //     }
        //     return true;
        // }

        if (empty($options['crop'])) {
            lib::resizeImg($file_path, $new_file_path, $max_width, $max_height, $quality, 5);
        } else {
			lib::resizeImg($file_path, $new_file_path, $max_width, $max_height, $quality, 7);
        }

        return true;
    }

    protected function trim_file_name($name, $type, $index, $content_range) {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:

        // Drop basename() cus it does not fully support chinese path
        $name = trim((stripslashes($name)));
        // Use a timestamp for empty filenames:
        if (!$name) {
            $name = str_replace('.', '-', microtime(true));
        }
        // Add missing file extension for known image types:
        if (strpos($name, '.') === false &&
            preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $name .= '.'.$matches[1];
        }
        return $name;
    }

    protected function get_unique_filename($name, $type, $index, $content_range) {
        $this->oriFileName = $name;

        $ext =  end(explode(".", $name));
        $name = lib::makeAuth($name) . "." . $ext;

        $uploaded_bytes = $this->fix_integer_overflow(intval($content_range[1]));
        while( is_file($this->get_upload_path($name)) ) {
            if ($uploaded_bytes === $this->get_file_size($this->get_upload_path($name))) {
                break;
            }
            $name = lib::makeAuth($name) . "." . $ext;
        }
        return $name;
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
        $file = new stdClass();
        $file->name = $this->get_file_name($name, $type, $index, $content_range);
        $file->oriFileName = $this->oriFileName;
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;
        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, $this->options['mkdir_mode'], true);
            }
            $file_path = $this->get_upload_path($file->name);
            $append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);

            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                    // move_uploaded_file($uploaded_file, iconv("utf-8", "big5", $file_path));
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = $this->get_file_size($file_path, $append_file);
            if ($file_size === $file->size) {
                // upload complete

                $final_file_path = $this->get_final_path($file->name);

                $file->name = substr($final_file_path, strrpos($final_file_path, '/') + 1);

                copy($file_path, $final_file_path);
                @unlink($file_path);

                $file_path = $final_file_path;

                $file->url = $this->get_download_url($file->name);
                list($img_width, $img_height) = @getimagesize($file_path);
                if (is_int($img_width)) {
                    $file->imgSize = "{$img_width}x{$img_height}";
                    $this->handle_image_file($file_path, $file);
                }
                $file = $this->save_to_db($file);
            } else {
                $file->size = $file_size;
                if (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file->error = 'abort';
                }
            }
            // $this->set_file_delete_properties($file);

        }
        return $file;
    }

    protected function handle_image_file($file_path, $file) {
        if ($this->options['orient_image']) {
            $this->orient_image($file_path);
        }
        $failed_versions = array();
        foreach($this->options['image_versions'] as $version => $options) {
            if ($this->create_scaled_image($file_path, $version, $options)) {
                if (!empty($version)) {
                    $file->{$version.'_url'} = $this->get_download_url(
                        $file->name,
                        $version
                    );
                } else {
                    $file->size = $this->get_file_size($file_path, true);
                }
            } else {
                $failed_versions[] = $version;
            }
        }
        switch (count($failed_versions)) {
            case 0:
                break;
            case 1:
                $file->error = 'Failed to create scaled version: '
                    .$failed_versions[0];
                break;
            default:
                $file->error = 'Failed to create scaled versions: '
                    .implode($failed_versions,', ');
        }
    }

    protected function save_to_db($file) {
	
		$cdn_url = url::sysdata_cdn();
		
        $pageId = $this->get_user_id();
        db::query("INSERT INTO file_upload SET pageId=:pageId, path=:path, srcName=:srcName, name=:name, createTime=NOW(), size=:size, userID=:userID, mimeType=:mimeType, imgSize=:imgSize", array(
            ':pageId' => strtolower($pageId),
            ':path'   => "{$file->url}",
            ':srcName'=> "{$file->oriFileName}",
            ':name'   => $file->name,
            ':size'   => $file->size,
            ':userID' => profile::$id,
            ':mimeType' => $file->type,
            ':imgSize'=> "{$file->imgSize}"
        ));

        $file->id = db::lastInsertId();
        $file->delAuth = lib::lockUrl($this->DELETE_URL, array('pageId'=>$pageId, 'id'=>$file->id));

        $key = mod_fileUpload_helper::getKey($file->id);
        if (strpos($file->type, "image") !== FALSE) {
			$file->cdn_url = $cdn_url . $file->url;
			$file->url = $file->url;
            $file->thumbnail_url = str_replace($file->name, "thumbnail/{$file->name}", $file->url);
        } else {
            $file->cdn_url = $cdn_url . "/sys/modules/mod_fileUpload/download.php?id={$file->id}&key={$key}";
            $file->url = "/sys/modules/mod_fileUpload/download.php?id={$file->id}&key={$key}";

            list($contentType, $subType) = explode("/", $file->type);
            $fileTypeUrl = prog::get('mod_fileUpload')->defaultPhoto;
            $file->thumbnail_url = $fileTypeUrl[$contentType];
        }

        mod_fileUpload_helper::quotaCompute($pageId, $size);

		sys::incSizeUsed('attachUsed',	$file->size);
		sys::incSizeUsed('spaceUsed',	$file->size);

        return $file;
    }

}