<?php

namespace vorozhok;

// Это стандартный класс для передачи данных в вид и layout
class StdData
{
    public function __construct(array $props = []){
        $this->setProps($props);
    }
	
	public function setProp($name, $value){
        $this->$name = $value;
    }
	
	public function setProps(array $props){
        foreach($props as $name=>$value){
			$this->$name = $value;
		}
    }
    
	public function getProp($name){
        return $this->$name;
    }
	
    public function getProps(){
        $objectVars = get_object_vars($this);
		$props = [];
		foreach ($objectVars as $key => $value) {
			$props[$key] = $value;
		}
		return $props;
    }
}