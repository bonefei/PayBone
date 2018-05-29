<?php
//公共方法
function test()
{
    echo 111;
}
function un_set($id, $source)
{
    if (is_array($id)) {
        foreach ($id as $k => $v) {
            if (isset($source[$v])) {
                unset($source[$v]);
            }

        }
    } else {
        if (isset($source[$id])) {
            unset($source[$id]);
        }

    }
    return $source;
}
function is_set_val($parameter, $key, $val, $type = '')
{
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            if ($type == 'add') {
                $val = $v . $val;
            }
            if (isset($parameter[$v])) {
                $parameter[$v] = $val;
            }

        }
    } else {
        if ($type == 'add') {
            $val = $parameter[$key] . $val;
        }
        if (isset($parameter[$key])) {
            $parameter[$key] = $val;
        }

    }
    return $parameter;
}
function is_set_arr($arr1, $arr2)
{
    foreach ($arr1 as $k => $v) {
        foreach ($arr2 as $kk => $vv) {
            if ($k == $kk) {
                $arr1[$k] = $vv;
            }
        }
    }
    return $arr1;
}
function object2array(&$object)
{
    $object = json_decode(json_encode($object), true);
    return $object;
}
function checkExcept($key, $arrKey,$keywords = null)
{
    if (!empty(strtolower($arrKey))) {
        if (strpos($arrKey, ',')) {
            if (in_array($key, explode(',', $arrKey))) {
                return true;
            } else {
                if($keywords){
                    foreach ($arrKey as $kk => $v) {
                        if (strpos($key, $v)) {
                            return true;
                        }
                    }
                }
            }
        } else {
            if ($key == $arrKey) {
                return true;
            }
        }
    }
    return false;
}
