<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper/
 * @file          AM_Notifications.php
 * @author        Ulrich Bittner
 * @copyright     2023, 2024 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait AM_Notifications
{
    ########## Protected

    /**
     * Sends a notification.
     *
     * @param int $NotificationType
     * 0 =  OK,
     * 1 =  Alarm
     *
     * @param string $DetectorName
     *
     * @return void
     * @throws Exception
     */
    protected function SendNotification(int $NotificationType, string $DetectorName): void
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
     * 0 =  OK,
     * 1 =  Alarm
     *
     * @param string $DetectorName
     *
     * @return void
     * @throws Exception
     */
    protected function SendPushNotification(int $NotificationType, string $DetectorName): void
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
     * Sends a post notification for the new tile visualisation.
     *
     * @param int $NotificationType
     * 0 =  OK,
     * 1 =  Alarm
     *
     * @param string $DetectorName
     *
     * @return void
     * @throws Exception
     */
    protected function SendPostNotification(int $NotificationType, string $DetectorName): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $elements = $this->ReadPropertyString('PostNotification');
        if ($NotificationType == 1) {
            $elements = $this->ReadPropertyString('PostNotificationAlarm');
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
            $scriptText = 'VISU_PostNotificationEx(' . $id . ', "' . $title . '", "' . $text . '", "' . $element['Icon'] . '", "' . $element['Sound'] . '", ' . $element['TargetID'] . ');';
            IPS_RunScriptText($scriptText);
        }
    }

    /**
     * Sends an e-mail.
     *
     * @param int $NotificationType
     * 0 =  OK,
     * 1 =  Alarm
     *
     * @param string $VariableValues
     *
     * @return void
     * @throws Exception
     */
    protected function SendMail(int $NotificationType, string $VariableValues): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $variable = json_decode($VariableValues, true);
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
            if ($element['UseLastUpdate']) {
                $lastUpdate = $variable['LastUpdate'];
            }
            if ($element['UseOverdueSince']) {
                $overdueSince = $variable['OverdueSince'];
            }
            //Build text
            if (isset($timestamp)) {
                $text .= $timestamp . "\n\n";
            }
            $text .= $message . "\n\n";
            $text .= 'ID: ' . $variable['ID'] . "\n\n";
            if (isset($lastUpdate)) {
                $text .= 'Letzte Aktualisierung: ' . $lastUpdate . "\n\n";
            }
            if (isset($overdueSince)) {
                if ($overdueSince != '') {
                    $text .=
                        'Überfällig seit: ' . $overdueSince;
                }
            }
            if ($text != '') {
                $scriptText = 'MA_SendMessage(' . $id . ', "' . $element['Subject'] . '", "' . $text . '");';
                IPS_RunScriptText($scriptText);
            }
        }
    }
}