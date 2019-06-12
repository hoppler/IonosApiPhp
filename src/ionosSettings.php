<?php

namespace IonosApi {

    class IonosSettings
    {
        public $Username;
        public $Password;
        public $RootDomain;
        public $IsWwwRecord;
        public $Ttl;
        public $AuthKey;
        public $AllowedDomains;

        private function __construct()
        {
        }

        /**
         * @param $fileName string File with json settings
         * @return IonosSettings
         */
        public static function FromFile($fileName)
        {
            if(!file_exists($fileName))
                return null;

            $fileContent = file_get_contents($fileName);
            $fileJson = json_decode($fileContent);

            $settings = new IonosSettings();
            $settings->Username = $fileJson->username;
            $settings->Password = $fileJson->password;
            $settings->RootDomain = $fileJson->rootDomain;
            $settings->IsWwwRecord = $fileJson->isWwwRecord;
            $settings->Ttl = $fileJson->ttl;
            $settings->AuthKey = $fileJson->authKey;
            $settings->AllowedDomains = $fileJson->allowedDomains;

            return $settings;
        }
    }
}