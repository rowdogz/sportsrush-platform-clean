<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author CMSHelplive
 */
class Element_RichText extends Element
{

    public function __construct($value,$class=null, array $properties = array())
    {
        $properties = array_merge($properties,array("value" => $value, "class" => $class));
        parent::__construct("", "", $properties);
    }

    public function render()
    {
        $this->renderTag("prepend");
        echo wp_kses_post((string)$this->_attributes["value"]);
        $this->renderTag("append");
    }
    
    public function renderTag($type = "prepend"){ 
        if($type === "prepend")
        {
            //Extract color attribute so that it can be applied to text.
            $style_str = "";
            if(isset($this->_attributes["style"]))
            {
                $al = explode(';',(string)$this->_attributes["style"]);                    
                foreach($al as $a)
                {
                    if(strpos(trim((string)$a),"color:")=== 0)
                    {
                        $style_str ='style="'.$a.'";'; 
                        break;
                    }
                }
            }
            echo '<div ' . wp_kses_post((string)$style_str) . ' class="rm_form_field_type_richtext' . ($this->_attributes["class"] ? ' ' . esc_attr($this->_attributes["class"]) : '') . '">';
        }
        if($type === "append")
            echo '</div>';
    }

    public function add_condition(){
        if(!is_null($this->_attributes["options"]) && isset($this->_attributes["options"]["data-cond-option"])) {
            echo '<input type="hidden" class="'.$this->_attributes["options"]["class"].'" data-cond-option="'.$this->_attributes["options"]["data-cond-option"].'" data-cond-value="'.$this->_attributes["options"]["data-cond-value"].'" data-cond-operator="'.$this->_attributes["options"]["data-cond-operator"].'" data-cond-action="'.$this->_attributes["options"]["data-cond-action"].'">';
        }
    }

}