<?php

namespace Zofe\Rapyd\Mechanisms;

use Illuminate\View\Compilers\ComponentTagCompiler;

class RapydTagPrecompiler extends ComponentTagCompiler
{
    public function __invoke($value, $params = [])
    {
        if(count($params) && isset($params['ref'])) {
            if($params['ref'] === 'link-view') {
                $value = $this->replaceLinkViewTags($value,  '/<a\s+data-ref="link-view"\s+href="#">(.*?)<\/a>/', $params);
            }
            if ($params['ref'] === 'link-edit') {
                $value = $this->replaceLinkEditTags($value, '/<a\s+data-ref="link-edit"\s+href="#">(.*?)<\/a>/', $params);
            }
            if ($params['ref'] === 'link-add') {
                $value = $this->replaceLinkAddTags($value, '/<a\s+data-ref="link-add"\s+href="#"><\/a>/', $params);
            }
            if ($params['ref'] === 'redirect') {
                $value = $this->replaceRedirect($value, '/#redirect/', $params);
            }

        }

        return $value;
    }

    protected function replaceLinkViewTags($value, $pattern, $params = [])
    {
        return preg_replace_callback($pattern, function (array $matches) use ($params) {
            $content = $matches[1];
            $viewId = str_replace(['{{', '}}'], '', $content);
            if(count($params) && isset($params['route'])) {

                return '<a href="{{ route_lang(\'' . $params['route'] . '\','.$viewId.') }}">' . $content . '</a>';
            }

            return $matches[0];
        }, $value);
    }

    protected function replaceLinkEditTags($value, $pattern, $params = [])
    {
        return preg_replace_callback($pattern, function (array $matches) use ($params) {
            $content = $matches[1];
            $viewId = str_replace(['{{', '}}'], '', $content);
            if(count($params) && isset($params['route'])) {
                return '<a href="{{ route_lang(\'' . $params['route'] . '\','.$viewId.') }}" class="btn btn-outline-primary">Edit</a>';
            }

            return $matches[0];
        }, $value);
    }

    protected function replaceLinkAddTags($value, $pattern, $params = [])
    {
        return preg_replace_callback($pattern, function (array $matches) use ($params) {
            if(count($params) && isset($params['route'])) {
                return '<a href="{{ route_lang(\'' . $params['route'] . '\') }}" class="btn btn-outline-primary">Add</a>';
            }
            return $matches[0];
        }, $value);
    }

    protected function replaceRedirect($value, $pattern, $params = [])
    {
        return preg_replace_callback($pattern, function (array $matches) use ($params) {
            if(count($params) && isset($params['route']) && isset($params['route_parameter'])) {
                $parameter = str_replace('$','$this->',$params['route_parameter']);
                return 'return redirect()->to(route_lang("'.$params['route'].'",'.$parameter.' ));';
            }

            return $matches[0];
        }, $value);
    }
}
