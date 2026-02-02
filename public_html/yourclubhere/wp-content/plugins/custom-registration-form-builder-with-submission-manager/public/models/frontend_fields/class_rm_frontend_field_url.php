<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class RM_Frontend_Field_URL extends RM_Frontend_Field_Base
{
    public $field_value;
    public $field_class;
            
    public function get_pfbc_field()
    {
        if ($this->pfbc_field)
            return $this->pfbc_field;
        else
        {
            $class_name = "Element_" . $this->field_type;
            //Add hidden field name as field id so user can modify field using JS if required.
            $value = isset($this->field_options['value']) ? $this->field_options['value'] : '';
            $this->pfbc_field[] = new Element_Hidden($this->field_name."[text]", '');
            $this->pfbc_field[] = new $class_name($this->field_label, $this->field_name."[url]", $this->field_options);
            return $this->pfbc_field;
        }  
    }

    public function get_prepared_data($request)
    {
        $data = new stdClass;
        $value = array('text' => '', 'url' => '');
        $data->field_id = $this->get_field_id();
        $data->type = $this->get_field_type();
        $data->label = $this->get_field_label();
        if (isset($request[$data->type.'_'.$data->field_id])) {
            $value['text'] = $request[$data->type.'_'.$data->field_id]['text'];
            $value['url'] = $request[$data->type.'_'.$data->field_id]['url'];
        }
        $data->value = $value;
        return $data;
    }
}