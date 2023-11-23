<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper
 * @file          AM_Notifications.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnusedPrivateMethodInspection */
/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

trait AM_Notifications
{
    /**
     * Sends a notification to the WebFront.
     *
     * @param int $NotificationType
     * 0 =  OK
     * 1 =  Alarm
     *
     * @param string $DetectorName
     *
     * @return void
     * @throws Exception
     */
    private function SendNotification(int $NotificationType, string $DetectorName): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $elements = $this->ReadPropertyString('Notification');
        if ($NotificationType == 1) {
            $elements = $this->ReadPropertyString('NotificationAlarm');
        }
        foreach (json_decode($elements, true) as $element) {
            if (!$element['Use']) {
                continue;
            }
            $id = $element['ID'];
            if ($id <= 1 || @!IPS_ObjectExists($id)) {
                continue;
            }
            $text = sprintf($element['Text'], $DetectorName);
            if ($element['UseTimestamp']) {
                $text = $text . ' ' . date('d.m.Y, H:i:s');
            }
            $scriptText = 'WFC_SendNotification(' . $id . ', "' . $element['Title'] . '", "' . $text . '", "' . $element['Icon'] . '", ' . $element['DisplayDuration'] . ');';
            IPS_RunScriptText($scriptText);
        }
    }

    /**
     * Sends a push notification.
     *
     * @param int $NotificationType
     * 0 =  OK
     * 1 =  Alarm
     *
     * @param string $DetectorName
     *
     * @return void
     * @throws Exception
     */
    private function SendPushNotification(int $NotificationType, string $DetectorName): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $elements = $this->ReadPropertyString('PushNotification');
        if ($NotificationType == 1) {
            $elements = $this->ReadPropertyString('PushNotificationAlarm');
        }
        foreach (json_decode($elements, true) as $element) {
            if (!$element['Use']) {
                continue;
            }
            $id = $element['ID'];
            if ($id <= 1 || @!IPS_ObjectExists($id)) {
                continue;
            }
            //Title length max 32 characters
            $title = substr($element['Title'], 0, 32);
            //Text
            $text = "\n" . sprintf($element['Text'], $DetectorName);
            if ($element['UseTimestamp']) {
                $text = $text . ' ' . date('d.m.Y, H:i:s');
            }
            //Text length max 256 characters
            $text = substr($text, 0, 256);
            $scriptText = 'WFC_PushNotification(' . $id . ', "' . $title . '", "' . $text . '", "' . $element['Sound'] . '", ' . $element['TargetID'] . ');';
            IPS_RunScriptText($scriptText);
        }
    }

    /**
     * Sends a mail notification.
     *
     * @param int $NotificationType
     * 0 =  OK
     * 1 =  Alarm
     *
     * @param int $VariableID
     *
     * @return void
     * @throws Exception
     */
    private function SendMailerNotification(int $NotificationType, int $VariableID): void //change to id
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $elements = $this->ReadPropertyString('MailerNotification');
        if ($NotificationType == 1) {
            $elements = $this->ReadPropertyString('MailerNotificationAlarm');
        }
        foreach (json_decode($elements, true) as $element) {
            if (!$element['Use']) {
                continue;
            }
            $id = $element['ID'];
            if ($id <= 1 || @!IPS_ObjectExists($id)) {
                continue;
            }
            //Prepare text
            $variables = json_decode($this->GetMonitoredVariables(), true);
            foreach ($variables as $variable) {
                if ($variable['ID'] == $VariableID) {
                    $text = '';
                    if ($element['UseTimestamp']) {
                        $timestamp = date('d.m.Y, H:i:s');
                    }
                    $comment = $variable['Comment'];
                    if ($comment != '') {
                        $name = $variable['Name'] . ' (' . $comment . ')';
                    } else {
                        $name = $variable['Name'];
                    }
                    $message = sprintf($element['Text'], $name);
                    if ($element['UseUpdatePeriod']) {
                        $updatePeriod = $variable['UpdatePeriod'] . ' Tag(e)';
                    }
                    if ($element['UseLastUpdate']) {
                        $lastUpdate = $variable['LastUpdate'];
                    }
                    //Build text
                    if (isset($timestamp)) {
                        $text .= $timestamp . "\n\n";
                    }
                    $text .= $message . "\n\n";
                    if (isset($updatePeriod)) {
                        $text .= 'Zeitraum: ' . $updatePeriod . "\n";
                    }
                    if (isset($lastUpdate)) {
                        $text .= 'Letzte Aktualisierung: ' . $lastUpdate;
                    }
                    if ($text != '') {
                        $scriptText = 'MA_SendMessage(' . $id . ', "' . $element['Subject'] . '", "' . $text . '");';
                        IPS_RunScriptText($scriptText);
                    }
                }
            }
        }
    }
}