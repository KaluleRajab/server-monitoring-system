<?php

namespace psm\Txtmsg;


use Exception;
use psm\Txtmsg\Core;

class Africastalking extends Core
{

    protected function sendAfrica($to_, $message_, $from_ = null, $bulkSMSMode_ = 1, array $options_ = array())
    {



        if (strlen($to_) == 0 || strlen($message_) == 0) {
            throw new Exception('Please supply both to and message parameters', 1);
        }

        $params = array(
            'username' => $this->username,
            'to'       => $to_,
            'message'  => $message_,
        );

        if ($from_ !== null) {
            $params['from']        = $from_;
            $params['bulkSMSMode'] = $bulkSMSMode_;
        }

        //This contains a list of parameters that can be passed in $options_ parameter
        if (count($options_) > 0) {
            $allowedKeys = array(
                'enqueue',
                'keyword',
                'linkId',
                'retryDurationInHours'
            );

            //Check whether data has been passed in options_ parameter
            foreach ($options_ as $key => $value) {
                if (in_array($key, $allowedKeys) && strlen($value) > 0) {
                    $params[$key] = $value;
                } else {
                    throw new Exception("Invalid key in options array: [$key]", 1);
                }
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_URL, $this->originator);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'apikey: ' . $this->password
        ));
        $res = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($res, true);
        return $response['SMSMessageData'];
    }

    public function sendSMS($msg)
    {

        $to = implode(",", $this->recipients);
        $fmsg = "Server Monitor status: " . $msg;
        //do other things before sending like checking if the msg type user allowed to receive such, but for now just send
        $f = self::sendAfrica($to, $fmsg);

        //do other things after sending like marking msg as sent set notification settings from network status automatically


        //make a definate decision to retry batch or pass it..now its being passed
        array_reduce($f['Recipients'], function ($carry, $rece) {
            if ($rece['status'] == 'Success') {
                return true;
            }
            return $carry;
        }, false);


        return 1;
    }
}
