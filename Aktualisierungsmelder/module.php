<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/AM_autoload.php';

class Aktualisierungsmelder extends IPSModule
{
    //Helper
    use AM_ConfigurationForm;
    use AM_Notifications;
    use AM_MonitoredVariables;

    //Constants
    private const LIBRARY_GUID = '{D4EA0559-08BA-52EC-2933-E4530A2B5769}';
    private const MODULE_GUID = '{EAC3392A-00F4-AC39-230E-34C28BAAE9B3}';
    private const MODULE_PREFIX = 'AM';
    private const WEBFRONT_MODULE_GUID = '{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}';
    private const MAILER_MODULE_GUID = '{C6CF3C5C-E97B-97AB-ADA2-E834976C6A92}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');

        //Status values
        $this->RegisterPropertyString('StatusTextOK', 'OK');
        $this->RegisterPropertyString('StatusTextAlarm', 'Alarm');

        //Sensor list
        $this->RegisterPropertyBoolean('EnableAlarm', true);
        $this->RegisterPropertyString('SensorListStatusTextAlarm', '🔴 Aktualisierung überfällig');
        $this->RegisterPropertyBoolean('EnableOK', true);
        $this->RegisterPropertyString('SensorListStatusTextOK', '🟢 OK');

        //Update period
        $this->RegisterPropertyInteger('TimeValue', 120);
        $this->RegisterPropertyInteger('TimeBase', 1);
        $this->RegisterPropertyInteger('StartUpCheckMode', 1);

        //Trigger list
        $this->RegisterPropertyString('TriggerList', '[]');

        //Notification
        $this->RegisterPropertyString('NotificationAlarm', '[]');
        $this->RegisterPropertyString('PushNotificationAlarm', '[]');
        $this->RegisterPropertyString('MailerNotificationAlarm', '[]');
        $this->RegisterPropertyString('Notification', '[]');
        $this->RegisterPropertyString('PushNotification', '[]');
        $this->RegisterPropertyString('MailerNotification', '[]');

        //Visualisation
        $this->RegisterPropertyBoolean('EnableActive', true);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableTriggeringDetector', true);
        $this->RegisterPropertyBoolean('EnableLastCheck', true);
        $this->RegisterPropertyBoolean('EnableUpdateStatus', true);
        $this->RegisterPropertyBoolean('EnableStatusList', true);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Ok', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Alarm', 'Warning', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Status', $profile, 20);

        //Triggering detector
        $id = @$this->GetIDForIdent('TriggeringDetector');
        $this->RegisterVariableString('TriggeringDetector', 'Auslösender Melder', '', 30);
        $this->SetValue('TriggeringDetector', '');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('TriggeringDetector'), 'Eyes');
        }

        //Last check
        $id = @$this->GetIDForIdent('LastCheck');
        $this->RegisterVariableString('LastCheck', 'Letzte Überprüfung', '', 40);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LastCheck'), 'Clock');
        }

        //Status list
        $id = @$this->GetIDForIdent('StatusList');
        $this->RegisterVariableString('StatusList', 'Statusliste', 'HTMLBox', 50);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('StatusList'), 'Database');
        }

        ########## Attributes

        $this->RegisterAttributeString('UpdateList', '[]');
        $this->RegisterAttributeString('LastStatusList', '[]');
        $this->RegisterAttributeString('CriticalVariables', '[]');

        ########## Timer

        $this->RegisterTimer('UpdateStatus', 0, self::MODULE_PREFIX . '_UpdateStatus(' . $this->InstanceID . ', 0);');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Update status profiles
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (IPS_VariableProfileExists($profile)) {
            //Set new values
            IPS_SetVariableProfileAssociation($profile, 0, $this->ReadPropertyString('StatusTextOK'), 'Ok', 0x00FF00);
            IPS_SetVariableProfileAssociation($profile, 1, $this->ReadPropertyString('StatusTextAlarm'), 'Warning', 0xFF0000);
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $id = $variable['ID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $this->RegisterReference($id);
                    if ($variable['Use']) {
                        $this->RegisterMessage($id, VM_UPDATE);
                    }
                }
            }
        }

        //Clean up update list
        $variableUpdates = json_decode($this->ReadAttributeString('UpdateList'), true);
        foreach ($variableUpdates as $key => $variableUpdate) {
            $exists = false;
            foreach ($variables as $variable) {
                if ($key == $variable['ID']) {
                    $exists = true;
                }
            }
            if (!$exists) {
                unset($variableUpdates[$key]);
            }
        }
        $this->WriteAttributeString('UpdateList', json_encode($variableUpdates));

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('TriggeringDetector'), !$this->ReadPropertyBoolean('EnableTriggeringDetector'));
        IPS_SetHidden($this->GetIDForIdent('LastCheck'), !$this->ReadPropertyBoolean('EnableLastCheck'));
        IPS_SetHidden($this->GetIDForIdent('StatusList'), !$this->ReadPropertyBoolean('EnableStatusList'));

        $this->StartUpCheck();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Status'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        $this->SendDebug(__FUNCTION__, 'Timestamp: ' . time(), 0);
        foreach ($Data as $key => $value) {
            $this->SendDebug(__FUNCTION__, 'Data [' . $key . ']: ' . $value, 0);
        }
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:
                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp last value
                //$Data[5] = timestamp value changed

                $updateList = json_decode($this->ReadAttributeString('UpdateList'), true);
                $updateList[$SenderID] = ['ActualTimestamp' => $Data[3], 'LastTimestamp' => $Data[4]];
                $this->WriteAttributeString('UpdateList', json_encode($updateList));
                $this->UpdateStatus($SenderID);
                break;

        }
    }

    /**
     * Creates a new specified module instance.
     *
     * @param string $ModuleName
     * @return void
     */
    public function CreateInstance(string $ModuleName): void
    {
        $this->SendDebug(__FUNCTION__, 'Modul: ' . $ModuleName, 0);
        switch ($ModuleName) {
            case 'WebFront':
                $guid = self::WEBFRONT_MODULE_GUID;
                $name = 'WebFront';
                break;

            case 'Mailer':
                $guid = self::MAILER_MODULE_GUID;
                $name = 'Mailer';
                break;

            default:
                return;
        }
        $this->SendDebug(__FUNCTION__, 'Guid: ' . $guid, 0);
        $id = @IPS_CreateInstance($guid);
        if (is_int($id)) {
            IPS_SetName($id, $name);
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    /**
     * Deletes a variable from the specified attribute list.
     *
     * @param string $AttributeName
     * Name of the attribute
     *
     * @param int $VariableID
     * Variable to be deleted
     *
     * @return void
     * @throws Exception
     */
    public function DeleteVariableFromAttribute(string $AttributeName, int $VariableID): void
    {
        $elements = json_decode($this->ReadAttributeString($AttributeName), true);
        foreach ($elements as $key => $element) {
            if ($element == $VariableID) {
                unset($elements[$key]);
            }
        }
        $elements = array_values($elements);
        $this->WriteAttributeString($AttributeName, json_encode($elements));
    }

    /**
     * Shows a message in the UI.
     *
     * @param string $Message
     * @return void
     */
    public function ShowUIMessage(string $Message): void
    {
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $Message);
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'Active') {
            $this->SetValue($Ident, $Value);
            if ($Value) {
                //StartUpCheck
                $this->StartUpCheck();
            } else {
                $this->SetTimerInterval('UpdateStatus', 0);
            }
        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Checks for maintenance.
     *
     * @return bool
     * false =  no maintenance,
     * true =   maintenance
     */
    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    /**
     * Attempts to set a semaphore and repeats this up to 100 times if unsuccessful.
     *
     * @param string $Name
     * @return bool
     */
    private function LockSemaphore(string $Name): bool
    {
        for ($i = 0; $i < 100; $i++) {
            if (IPS_SemaphoreEnter(self::MODULE_PREFIX . '_' . $this->InstanceID . '_Semaphore_' . $Name, 1)) {
                $this->SendDebug(__FUNCTION__, 'Semaphore locked', 0);
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Unlocks a semaphore.
     *
     * @param string $Name
     */
    private function UnlockSemaphore(string $Name): void
    {
        IPS_SemaphoreLeave(self::MODULE_PREFIX . '_' . $this->InstanceID . '_Semaphore_' . $Name);
        $this->SendDebug(__FUNCTION__, 'Semaphore unlocked', 0);
    }

    private function StartUpCheck(): void
    {
        if ($this->CheckMaintenance()) {
            $this->SetTimerInterval('UpdateStatus', 0);
        }
        switch ($this->ReadPropertyInteger('StartUpCheckMode')) {
            case 0: //Immediate check
                $this->UpdateStatus();
                break;

            case 1: //Check at the next update period
                $this->SetTimerInterval('UpdateStatus', $this->GetWatchTime() * 1000);
                break;

        }
    }
}
