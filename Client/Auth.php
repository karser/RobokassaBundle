<?php
namespace Karser\RobokassaBundle\Client;

class Auth
{
    /** @var string */
    private $password1;

    /** @var string */
    private $password2;


    public function __construct($password1, $password2)
    {
        $this->password1 = $password1;
        $this->password2 = $password2;
    }

    public function sign($login, $out_sum, $inv_id)
    {
        return md5($login . ':' . $out_sum . ':' . $inv_id . ':' . $this->password1);
    }

    public function signXML($login, $inv_id)
    {
        return md5($login . ':' . $inv_id . ':' . $this->password2);
    }


    public function validateResult($sign, $out_sum, $inv_id)
    {
        $crc = md5($out_sum . ':' . $inv_id . ':' . $this->password2);
        return strtoupper($sign) === strtoupper($crc);
    }

    public function validateSuccess($sign, $out_sum, $inv_id)
    {
        $crc = md5($out_sum . ':' . $inv_id . ':' . $this->password1);
        return strtoupper($sign) === strtoupper($crc);
    }
}