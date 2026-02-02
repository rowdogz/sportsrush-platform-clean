<?php
class Validation_MinLength extends Validation {
	public $message;
    public $base_val;

	public function __construct($min,$message = "") {
        $this->base_val = $min;
		$this->message = str_replace("%s",(string)$this->base_val,__("%element% must have more than or equal to %s characters.","custom-registration-form-builder-with-submission-manager"));
	}
    
    public function getMessage() {
		return $this->message;
	}

    public function isNotApplicable($value) {
		return false;
	}

	public function isValid($value) {
		if(strlen((string)$value) >= $this->base_val)
			return true;
		return false;
	}
}