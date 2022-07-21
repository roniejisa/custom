<?php

namespace Roniejisa\Custom\Helpers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class RSCustom
{
    public static function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $paginate = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        $paginate = $paginate->setPath(str_replace(url('/'), '', url()->current()));
        return $paginate;
    }

    public static function isDateTime($string, $format = 'Y-m-d H:i:s')
    {
        return \DateTime::createFromFormat($format, $string);
    }

    public static function showTime($time, $isDateTime = false, $format = "H:i d/m/Y")
    {
        if ($isDateTime) {
            $date = new DateTime($time);
            return $date->format($format);
        }
        $updateTime = strtotime($time);
        $now = strtotime(date("Y-m-d H:i:s"));
        $time = $updateTime - $now;
        $day = floor($time / (24 * 60 * 60));
        $hour = floor($time / (60 * 60));
        $minutes = floor($time / 60);
        $second = floor($time);
        $isReverse = false;
        if ($time < 0) {
            $isReverse = true;
            $time = $now - $updateTime;
        }

        if ($time >= (365 * 24 * 60 * 60)) {
            $year = floor(($time / (365 * 24 * 60 * 60)));
            $value = $year . " năm nữa";
        } elseif ($time >= (30 * 24 * 60 * 60)) {
            $month = floor($time / (30 * 24 * 60 * 60));
            $value = $month . " tháng nữa";
        } elseif ($time >= (2 * 24 * 60 * 60)) {
            $value = date("H:i d/m/Y", $updateTime);
        } elseif ($time >= (24 * 60 * 60)) {
            $value = $day . " ngày nữa";
        } elseif ($time >= (60 * 60)) {
            $value = $hour . " giờ nữa";
        } elseif ($time >= 60) {
            $value = $minutes . " phút nữa";
        } else {
            $value = $second . " giây nữa";
        }
        if ($isReverse) {
            $value = str_replace(['-', 'nữa'], ['', 'trước'], $value);
        }

        return $value;
    }

    public static function showDateName($string, $format = 'l d-m-Y')
    {
        if (self::isDateTime($string)) {
            $stringName = Carbon::parse($string)->format($format);
            return self::dateNameToVi($stringName);
        }
    }

    public static function dateNameToVi($string)
    {
        if (is_int(strpos($string, 'Monday'))) {
            $day = str_replace('Monday', 'Thứ 2', $string);
        } elseif (is_int(strpos($string, 'Tuesday'))) {
            $day = str_replace('Tuesday', 'Thứ 3', $string);
        } elseif (is_int(strpos($string, 'Wednesday'))) {
            $day = str_replace('Wednesday', 'Thứ 4', $string);
        } elseif (is_int(strpos($string, 'Thursday'))) {
            $day = str_replace('Thursday', 'Thứ 5', $string);
        } elseif (is_int(strpos($string, 'Friday'))) {
            $day = str_replace('Friday', 'Thứ 6', $string);
        } elseif (is_int(strpos($string, 'Saturday'))) {
            $day = str_replace('Saturday', 'Thứ 7', $string);
        } elseif (is_int(strpos($string, 'Sunday'))) {
            $day = str_replace('Sunday', 'CN', $string);
        }
        return $day;
    }

    public static function createImageBase64($img)
    {
        if (is_array($img)) {
            $path = public_path($img['path'] . $img['name']);
            $path = file_exists($path) ? $path : public_path(str_replace('public/', '', $img['path']) . $img['name']);
        } elseif (is_object($img)) {
            $path = public_path($img->path . $img->name);
            $path = file_exists($path) ? $path : public_path(str_replace('public/', '', $img->path) . $img->name);
        } else {
            $path = $img;
        }
        if (!file_exists($path)) {
            return ['', '', ''];
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $name = pathinfo($path, PATHINFO_BASENAME);
        $type = in_array($type, ['jpg', 'gif', 'jpeg', 'webp', 'png', 'tiff', '']) ? "image/$type" : "video/$type";
        $data = file_get_contents($path);
        $base64 = 'data:' . $type . ';base64,' . base64_encode($data);
        return [$base64, $name, $type];
    }

    public static function checkActive($haystack, $needle = true, $show = 'active')
    {
        $needle = $needle ? url()->current() : $needle;
        $arrayHayStack = is_array($haystack) ? $haystack : [$haystack];
        return in_array($needle, $arrayHayStack) ? $show : '';
    }

    public static function checkActiveParam($param, $arrayValue = [], $show = 'active')
    {
        $array = is_array($arrayValue) ? $arrayValue : [$arrayValue];

        if (is_array(request()->input($param))) {
            return count(array_intersect(request()->input($param), $array)) > 0 ? $show : '';
        } else {
            return in_array(request()->input($param), $array) ? $show : '';
        }
    }

    public static function insertFileFromUrl($folder, $url)
    {
        $urlCut = explode('/', $url);
        $name = $urlCut[count($urlCut) - 1];
        $name = explode('.', $name)[0];
        if (!preg_match('/^[a-z0-9]+$/', $folder)) {
            throw new \Exception("Tên thư mục chỉ được chứa các ký tự [a-z0-9]");
        }
        $fileContent = file_get_contents($url);
        if (empty($fileContent)) {
            return;
        }
        $fileExtension = strtolower(substr($url, strrpos($url, '.') + 1));
        $fileFullName = $name . '.' . $fileExtension;
        $fileFullPath = $folder . '/' . $fileFullName;
        file_put_contents(public_path($fileFullPath), $fileContent);
        return $fileFullPath;
    }

    public static function replaceToCharacter($string, $character, $isStart = true, $lengthShow = 2, $after = false)
    {
        $afterString = '';
        if ($after) {
            list($string, $afterString) = explode($after, $string);
            $afterString = $after . $afterString;
        }
        $length = mb_strlen($string) - $lengthShow;
        $string = substr($string, $isStart ? -$lengthShow : 0, $lengthShow);
        for ($i = 0; $i < $length; $i++) {
            if ($isStart) {
                $string = $character . $string;
            } else {
                $string .= $character;
            }
        }
        return $string . $afterString;
    }

    public static function URLPrevious($isSet = true, $defaultUrl = '/')
    {
        $key = 'PREVIOUS_URL_RS';
        if ($isSet) {
            $url = url()->previous();
            $url = strpos($url, url('/')) >= 0 ? $url : url($defaultUrl);
            session()->put($key, $url);
        }

        if (!$isSet) {
            $url = is_null(($url = session()->get($key))) ? url()->previous() : $url;
            $url = $url == url()->current() ? url($defaultUrl) : $url;
            session()->forget($key, null);
        }

        $arrayNotRedirect = ['.js', '.css', '_debugbar', 'javascript', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'];
        foreach ($arrayNotRedirect as $error) {
            if (strpos($url, $error) !== false) {
                $url = url($defaultUrl);
                break;
            }
        }
        return $url;
    }

    public static function setLinkCurrent($array)
    {
        return request()->fullUrlWithQuery(array_merge(request()->all(), $array));
    }

    // Lấy param trong url
    public static function getParamUrl($url, $name = 'page')
    {
        $url = parse_url($url);
        parse_str($url['query'], $params);
        return $params[$name];
    }

    // Cut string sau từ đầu đến trước từ cuối
    public static function cutString($str, $start = "item-code=", $end = "&amp;")
    {
        $cutFirst = substr($str, strpos($str, $start) + strlen($start));
        return substr($cutFirst, 0, strpos($cutFirst, $end));
    }

    // Lưu log
    public static function saveLog($pathAll, $content)
    {
        $paths = explode('/', $pathAll);
        $path = '';
        for ($index = 0; $index < count($paths) - 1; $index++) {
            $path .= $index == 0 ? $paths[$index] : '/' . $paths[$index];
            if (!file_exists(public_path($path))) {
                mkdir(public_path($path), 0777, true);
            }
        }
        file_put_contents(public_path($pathAll), $content, FILE_APPEND);
    }

    public static function test(){
        return 'test update';
    }
}
