<?php
namespace PaysonEmbedded{
    class Gui{
        /** @var string $colorScheme Color scheme of the checkout snippet("white", "black", "blue" (default), "red"). */
        public $colorScheme;
        /** @var string $locale Used to change the language shown in the checkout snippet ("se", "fi", "en" (default)). */
        public $locale;
        /** @var string $verfication  Can be used to add extra customer verfication ("bankid", "none" (default)). */
        public $verfication;
        /** @var bool $requestPhone  Can be used to require the user to fill in his phone number. */
        public $requestPhone;
        
        public function __construct($locale = "sv", $colorScheme = "gray", $verfication = "none", $requestPhone = NULL){
            $this->colorScheme = $colorScheme;
            $this->locale = $locale; 
            $this->verfication = $verfication;
            $this->requestPhone = $requestPhone;
        }

        public static function create($data) {
            return new Gui($data->locale, $data->colorScheme, $data->verification, $data->requestPhone);
        }
        
        public function toArray(){
            return get_object_vars($this);      
        }
    }
}

