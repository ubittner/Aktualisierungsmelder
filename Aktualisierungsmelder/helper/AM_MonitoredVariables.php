<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper/
 * @file          AM_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

trait AM_MonitoredVariables
{
    /**
     * Checks the time value. Restricted to 21 days maximum.
     *
     * @param int $TimeBase
     * @return void
     */
    public function CheckTimeValue(int $TimeBase): void
    {
        switch ($TimeBase) {
            case 1: //Minutes
                $minimum = 1;
                $maximum = 30240;
                break;

            case 2: //Hours
                $minimum = 1;
                $maximum = 504;
                break;

            case 3: //Days
                $minimum = 1;
                $maximum = 21;
                break;

            default: //Seconds
                $minimum = 10;
                $maximum = 1814400;
        }
        $this->UpdateFormfield('TimeValue', 'minimum', $minimum);
        $this->UpdateFormfield('TimeValue', 'maximum', $maximum);
    }

    /**
     *  Checks the determination value for the variable.
     *
     * @param int $VariableDeterminationType
     * @return void
     */
    public function CheckVariableDeterminationValue(int $VariableDeterminationType): void
    {
        $profileSelection = false;
        $determinationValue = false;
        //Profile selection
        if ($VariableDeterminationType == 0) {
            $profileSelection = true;
        }
        //Custom ident
        if ($VariableDeterminationType == 8) {
            $this->UpdateFormfield('VariableDeterminationValue', 'caption', 'Identifikator');
            $determinationValue = true;
        }
        $this->UpdateFormfield('ProfileSelection', 'visible', $profileSelection);
        $this->UpdateFormfield('VariableDeterminationValue', 'visible', $determinationValue);
    }

    /**
     * Determines the variables.
     *
     * @param int $DeterminationType
     * @param string $DeterminationValue
     * @param string $ProfileSelection
     * @return void
     * @throws Exception
     */
    public function DetermineVariables(int $DeterminationType, string $DeterminationValue, string $ProfileSelection = ''): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Auswahl: ' . $DeterminationType, 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $DeterminationValue, 0);
        //Set minimum an d maximum of existing variables
        $this->UpdateFormField('VariableDeterminationProgress', 'minimum', 0);
        $maximumVariables = count(IPS_GetVariableList());
        $this->UpdateFormField('VariableDeterminationProgress', 'maximum', $maximumVariables);
        //Determine variables first
        $determineIdent = false;
        $determineProfile = false;
        $determinedVariables = [];
        $passedVariables = 0;
        foreach (@IPS_GetVariableList() as $variable) {
            switch ($DeterminationType) {
                //Select profile
                case 0:
                    if ($ProfileSelection == '') {
                        $infoText = 'Abbruch, es wurde kein Profil ausgewählt!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineProfile = true;
                    }
                    break;

                    //Various idents
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                    $determineIdent = true;
                    break;

                    //Custom ident
                case 8:
                    if ($DeterminationValue == '') {
                        $infoText = 'Abbruch, es wurde kein Identifikator angegeben!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineIdent = true;
                    }
                    break;

            }
            $passedVariables++;
            $this->UpdateFormField('VariableDeterminationProgress', 'visible', true);
            $this->UpdateFormField('VariableDeterminationProgress', 'current', $passedVariables);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'visible', true);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
            IPS_Sleep(10);

            //Determine via profile
            if ($determineProfile && !$determineIdent) {
                if ($DeterminationType == 0) {
                    $profileName = $ProfileSelection;
                }
                if (isset($profileName)) {
                    $variableData = IPS_GetVariable($variable);
                    if ($variableData['VariableCustomProfile'] == $profileName || $variableData['VariableProfile'] == $profileName) {
                        $location = @IPS_GetLocation($variable);
                        $determinedVariables[] = [
                            'Use'      => false,
                            'ID'       => $variable,
                            'Location' => $location];
                    }
                }
            }

            //Determine via ident
            if ($determineIdent && !$determineProfile) {
                switch ($DeterminationType) {
                    case 1:
                        $objectIdents = 'LOWBAT';
                        break;

                    case 2:
                        $objectIdents = 'LOW_BAT';
                        break;

                    case 3:
                        $objectIdents = 'LOWBAT, LOW_BAT';
                        break;

                    case 4:
                        $objectIdents = 'STATE';
                        break;

                    case 5:
                        $objectIdents = 'MOTION';
                        break;

                    case 6:
                        $objectIdents = 'SMOKE_DETECTOR_ALARM_STATUS';
                        break;

                    case 7:
                        $objectIdents = 'ALARMSTATE';
                        break;

                    case 8: //Custom ident
                        $objectIdents = $DeterminationValue;
                        break;

                }
                if (isset($objectIdents)) {
                    $objectIdents = str_replace(' ', '', $objectIdents);
                    $objectIdents = explode(',', $objectIdents);
                    foreach ($objectIdents as $objectIdent) {
                        $object = @IPS_GetObject($variable);
                        if ($object['ObjectIdent'] == $objectIdent) {
                            $location = @IPS_GetLocation($variable);
                            $determinedVariables[] = [
                                'Use'      => false,
                                'ID'       => $variable,
                                'Location' => $location];
                        }
                    }
                }
            }
        }
        $amount = count($determinedVariables);
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($listedVariables as $listedVariable) {
            $listedVariableID = $listedVariable['ID'];
            if ($listedVariableID > 1 && @IPS_ObjectExists($listedVariableID)) {
                foreach ($determinedVariables as $key => $determinedVariable) {
                    $determinedVariableID = $determinedVariable['ID'];
                    if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                        //Check if variable id is already a listed variable id
                        if ($determinedVariableID == $listedVariableID) {
                            unset($determinedVariables[$key]);
                        }
                    }
                }
            }
        }
        if (empty($determinedVariables)) {
            $this->UpdateFormField('VariableDeterminationProgress', 'visible', false);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'visible', false);
            if ($amount > 0) {
                $infoText = 'Es wurden keine weiteren Variablen gefunden!';
            } else {
                $infoText = 'Es wurden keine Variablen gefunden!';
            }
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
            return;
        }
        $determinedVariables = array_values($determinedVariables);
        $this->UpdateFormField('DeterminedVariableList', 'rowCount', count($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'values', json_encode($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'visible', true);
        $this->UpdateFormField('ApplyPreTriggerValues', 'visible', true);
    }

    /**
     * Adds the selected variables to the trigger list.
     *
     * @param object $ListValues
     *
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function AddSelectedVariables(object $ListValues): void
    {
        $determinedVariables = [];
        $reflection = new ReflectionObject($ListValues);
        $property = $reflection->getProperty('array');
        $property->setAccessible(true);
        $variables = $property->getValue($ListValues);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['ID'];
            $name = @IPS_GetName($id);
            $address = '';
            $parent = @IPS_GetParent($id);
            if ($parent > 1 && @IPS_ObjectExists($parent)) {
                $parentObject = @IPS_GetObject($parent);
                if ($parentObject['ObjectType'] == 1) { //1 = instance
                    $name = strstr(@IPS_GetName($parent), ':', true);
                    if (!$name) {
                        $name = @IPS_GetName($parent);
                    }
                    $address = @IPS_GetProperty($parent, 'Address');
                    if (!$address) {
                        $address = '';
                    }
                }
            }
            $determinedVariables[] = [
                'Use'         => true,
                'ID'          => $id,
                'Designation' => $name,
                'Comment'     => $address
            ];
        }
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            $determinedVariableID = $determinedVariable['ID'];
            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                //Check variable id with already listed variable ids
                $add = true;
                foreach ($listedVariables as $listedVariable) {
                    $listedVariableID = $listedVariable['ID'];
                    if ($listedVariableID > 1 && @IPS_ObjectExists($listedVariableID)) {
                        if ($determinedVariableID == $listedVariableID) {
                            $add = false;
                        }
                    }
                }
                //Add new variable to already listed variables
                if ($add) {
                    $listedVariables[] = $determinedVariable;
                }
            }
        }
        if (empty($determinedVariables)) {
            return;
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
    }

    /**
     * Lists the actual variable states in the configuration form.
     *
     * @return void
     * @throws Exception
     */
    public function ListActualVariableStates(): void
    {
        $this->UpdateFormField('ActualVariableStatesConfigurationButton', 'visible', false);
        $actualVariableValues = [];
        $values = $this->GetMonitoredVariableValues();
        foreach (json_decode($values, true) as $variable) {
            $actualVariableValues[] = [
                'ActualStatus' => $variable['StatusText'],
                'ID'           => $variable['ID'],
                'Designation'  => $variable['Name'],
                'Comment'      => $variable['Comment'],
                'LastUpdate'   => $variable['LastUpdate'],
                'OverdueSince' => $variable['OverdueSince']
            ];
        }
        $amount = count($actualVariableValues);
        if ($amount == 0) {
            $amount = 1;
        }
        $this->UpdateFormField('ActualVariableStates', 'rowCount', $amount);
        $this->UpdateFormField('ActualVariableStates', 'values', json_encode($actualVariableValues));
        $this->UpdateFormField('ActualVariableStates', 'visible', true);
    }

    /**
     * Creates links of the selected monitored variables.
     *
     * @param int $LinkCategory
     * @param object $ListValues
     * @return void
     * @throws ReflectionException
     */
    public function CreateVariableLinks(int $LinkCategory, object $ListValues): void
    {
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            $this->ShowUIMessage('Abbruch, bitte wählen Sie eine Kategorie aus!');
            return;
        }
        $reflection = new ReflectionObject($ListValues);
        $property = $reflection->getProperty('array');
        $property->setAccessible(true);
        $variables = $property->getValue($ListValues);
        $amountVariables = 0;
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $amountVariables++;
            }
        }
        if ($amountVariables == 0) {
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', 'Es wurden keine Variablen ausgewählt!');
            return;
        }
        $maximumVariables = $amountVariables;
        $this->UpdateFormField('VariableLinkProgress', 'minimum', 0);
        $this->UpdateFormField('VariableLinkProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        $targetIDs = [];
        $i = 0;
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $passedVariables++;
                $this->UpdateFormField('VariableLinkProgress', 'visible', true);
                $this->UpdateFormField('VariableLinkProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableLinkProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableLinkProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                $id = $variable['SensorID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $targetIDs[$i] = ['name' => $variable['Designation'], 'targetID' => $id];
                    $i++;
                }
            }
        }
        //Sort array alphabetically by device name
        sort($targetIDs);
        //Get all existing links (links have not an ident field, so we use the object info field)
        $existingTargetIDs = [];
        $links = @IPS_GetLinkList();
        if (!empty($links)) {
            $i = 0;
            foreach ($links as $link) {
                $linkInfo = @IPS_GetObject($link)['ObjectInfo'];
                if ($linkInfo == self::MODULE_PREFIX . '.' . $this->InstanceID) {
                    //Get target id
                    $existingTargetID = @IPS_GetLink($link)['TargetID'];
                    $existingTargetIDs[$i] = ['linkID' => $link, 'targetID' => $existingTargetID];
                    $i++;
                }
            }
        }
        //Delete dead links
        $deadLinks = array_diff(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($deadLinks)) {
            foreach ($deadLinks as $targetID) {
                $position = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$position]['linkID'];
                if (@IPS_LinkExists($linkID)) {
                    @IPS_DeleteLink($linkID);
                }
            }
        }
        //Create new links
        $newLinks = array_diff(array_column($targetIDs, 'targetID'), array_column($existingTargetIDs, 'targetID'));
        if (!empty($newLinks)) {
            foreach ($newLinks as $targetID) {
                $linkID = @IPS_CreateLink();
                @IPS_SetParent($linkID, $LinkCategory);
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetLinkTargetID($linkID, $targetID);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
            }
        }
        //Edit existing links
        $existingLinks = array_intersect(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($existingLinks)) {
            foreach ($existingLinks as $targetID) {
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                $targetID = $targetIDs[$position]['targetID'];
                $index = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$index]['linkID'];
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
            }
        }
        $this->UpdateFormField('VariableLinkProgress', 'visible', false);
        $this->UpdateFormField('VariableLinkProgressInfo', 'visible', false);
        $infoText = 'Die Variablenverknüpfung wurde erfolgreich erstellt!';
        if ($amountVariables > 1) {
            $infoText = 'Die Variablenverknüpfungen wurden erfolgreich erstellt!';
        }
        $this->ShowUIMessage($infoText);
    }

    /**
     * Lists the critical variables in the configuration form.
     *
     * @return void
     * @throws Exception
     */
    public function ListCriticalVariables(): void
    {
        $this->UpdateFormField('CriticalVariablesConfigurationButton', 'visible', false);
        $criticalVariableListValue = [];
        $criticalVariables = json_decode($this->ReadAttributeString('CriticalVariables'), true);
        $amount = count($criticalVariables);
        if ($amount == 0) {
            $amount = 1;
        }
        if (is_array($criticalVariables)) {
            foreach ($criticalVariables as $variable) {
                $criticalVariableListValue[] = [
                    'ObjectID'         => $variable,
                    'Name'             => IPS_GetName($variable),
                    'VariableLocation' => IPS_GetLocation($variable)];
            }
        }
        $this->UpdateFormField('CriticalVariableList', 'rowCount', $amount);
        $this->UpdateFormField('CriticalVariableList', 'values', json_encode($criticalVariableListValue));
    }

    /**
     * Updates the status of one or all monitored variables.
     *
     * @param int $VariableID
     * @return void
     * @throws Exception
     */
    public function UpdateStatus(int $VariableID = 0): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        //Enter semaphore
        if (!$this->LockSemaphore('Update')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('Update');
            return;
        }
        $values = json_decode($this->GetMonitoredVariableValues(), true);
        $criticalVariables = json_decode($this->ReadAttributeString('CriticalVariables'), true);
        //Check all variables
        if ($VariableID == 0) {
            $this->SetTimerInterval('UpdateStatus', $this->GetWatchTime() * 1000);
            $actualOverallStatus = false;
            $triggeringDetectorName = '';
            foreach ($values as $variable) {
                $id = $variable['ID'];
                //Alarm
                if ($variable['ActualStatus'] == 1) {
                    $actualOverallStatus = true;
                    $triggeringDetectorName = $variable['Name'];
                    if (!in_array($id, $criticalVariables)) {
                        //Add to critical variables
                        $criticalVariables[] = $id;
                        //Notifications
                        $this->SendNotification(1, $variable['Name']);
                        $this->SendPushNotification(1, $variable['Name']);
                        $this->SendMail(1, json_encode($variable));
                    }
                }
                //OK
                if ($variable['ActualStatus'] == 0) {
                    if (in_array($id, $criticalVariables)) {
                        //Remove from critical variables
                        $criticalVariables = array_diff($criticalVariables, [$id]);
                        //Notifications
                        $this->SendNotification(0, $variable['Name']);
                        $this->SendPushNotification(0, $variable['Name']);
                        $this->SendMail(0, json_encode($variable));
                    }
                }
            }
            if ($this->GetValue('Status') != $actualOverallStatus) {
                $this->SetValue('Status', $actualOverallStatus);
            }
            if ($this->GetValue('TriggeringDetector') != $triggeringDetectorName) {
                $this->SetValue('TriggeringDetector', $triggeringDetectorName);
            }
            $this->SetValue('LastCheck', date('d.m.Y H:i:s', time()));
            $this->UpdateView(json_encode($values));
        } else {
            foreach ($values as $variable) {
                if ($variable['ID'] == $VariableID) {
                    $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $VariableID, 0);
                    //OK
                    if ($variable['ActualStatus'] == 0) {
                        $this->SendDebug(__FUNCTION__, 'ActualStatus: 0', 0);
                        if (in_array($VariableID, $criticalVariables)) {
                            $this->SendDebug(__FUNCTION__, 'Variable is in crtical list and will be removed!', 0);
                            //Remove from critical variables
                            $criticalVariables = array_diff($criticalVariables, [$VariableID]);
                            //Remove old variable values from the last status list
                            $listedVariables = json_decode($this->ReadAttributeString('LastStatusList'), true);
                            $this->SendDebug(__FUNCTION__, 'LastStatusList:' . json_encode($listedVariables), 0);
                            foreach ($listedVariables as $key => $listedVariable) {
                                if ($listedVariable['ID'] == $VariableID) {
                                    unset($listedVariables[$key]);
                                }
                            }
                            //Add new variable value to the status list
                            $listedVariables[] = $variable;
                            $this->SendDebug(__FUNCTION__, 'New LastStatusList:' . json_encode($listedVariables), 0);
                            //Check overall status
                            if ($this->CheckOverallStatus(json_encode($listedVariables)) == 0) {
                                if ($this->GetValue('Status')) {
                                    $this->SetValue('Status', false);
                                }
                                if ($this->GetValue('TriggeringDetector') != '') {
                                    $this->SetValue('TriggeringDetector', '');
                                }
                            }
                            //Update view
                            $this->UpdateView(json_encode($listedVariables));
                            //Notifications
                            $this->SendNotification(0, $variable['Name']);
                            $this->SendPushNotification(0, $variable['Name']);
                            $this->SendMail(0, json_encode($variable));
                        }
                    }
                }
            }
        }
        $this->WriteAttributeString('CriticalVariables', json_encode(array_values($criticalVariables)));
        //Leave semaphore
        $this->UnlockSemaphore('Update');
    }

    ########## Protected

    /**
     * Gets the watch time.
     *
     * @return int
     * @throws Exception
     */
    protected function GetWatchTime(): int
    {
        $timeBase = $this->ReadPropertyInteger('TimeBase');
        $timeValue = $this->ReadPropertyInteger('TimeValue');
        switch ($timeBase) {
            case 1: //Minutes
                return $timeValue * 60;

            case 2: //Hours
                return $timeValue * 3600;

            case 3: //Days
                return $timeValue * 86400;

            default: //Seconds
                return $timeValue;
        }
    }

    ########## Private

    /**
     * Gets the values of the monitored variables.
     *
     * @return string
     * @throws Exception
     */
    private function GetMonitoredVariableValues(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            $id = $variable['ID'];
            if ($id <= 1 || @!IPS_ObjectExists($id)) {
                continue;
            }
            if (!$variable['Use']) {
                continue;
            }
            //OK
            $actualStatus = 0;
            $statusText = $this->ReadPropertyString('SensorListStatusTextOK');
            $overdueSince = '';
            $variableUpdate = IPS_GetVariable($id)['VariableUpdated']; //timestamp or 0 = never
            $watchTime = $this->GetWatchTime();
            $watchTimeBorder = time() - $watchTime;
            //Alarm
            if ($variableUpdate < $watchTimeBorder) {
                $actualStatus = 1;
                $statusText = $this->ReadPropertyString('SensorListStatusTextAlarm');
                $timediff = time() - $variableUpdate;
                $overdueSince = $this->FormatTime($timediff);
            }
            $lastUpdate = 'Nie';
            if ($variableUpdate != 0) {
                $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
            }
            $result[] = [
                'ID'           => $id,
                'Name'         => $variable['Designation'],
                'Comment'      => $variable['Comment'],
                'ActualStatus' => $actualStatus,
                'StatusText'   => $statusText,
                'LastUpdate'   => $lastUpdate,
                'OverdueSince' => $overdueSince];
        }
        if (!empty($result)) {
            //Sort variables by name
            array_multisort(array_column($result, 'Name'), SORT_ASC, $result);
        }
        return json_encode($result);
    }

    /**
     * Formats the time into a string.
     *
     * @param int $Value
     * @return string
     */
    private function FormatTime(int $Value): string
    {
        $template = '';
        $number = 0;
        if ($Value < 60) {
            return 'Gerade eben';
        } elseif (($Value > 60) && ($Value < (60 * 60))) {
            $template = '%s Minute';
            $number = floor($Value / 60);
            if ($Value >= (2 * 60)) {
                $template .= 'n';
            }
        } elseif (($Value > (60 * 60)) && ($Value < (24 * 60 * 60))) {
            $template = '%s Stunde';
            $number = floor($Value / (60 * 60));
            if ($Value >= (2 * 60 * 60)) {
                $template .= 'n';
            }
        } elseif ($Value > (24 * 60 * 60)) {
            $template = '%s Tag';
            $number = floor($Value / (24 * 60 * 60));
            if ($Value >= (2 * 24 * 60 * 60)) {
                $template .= 'e';
            }
        }
        return sprintf($template, number_format($number, 0, '', '.'));
    }

    /**
     * Updates the visualisation view for the status list.
     *
     * @param string $MonitoredVariableValues
     * @return void
     * @throws Exception
     */
    private function UpdateView(string $MonitoredVariableValues): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Values: ' . json_encode($MonitoredVariableValues), 0);
        $variables = json_decode($MonitoredVariableValues, true);
        if (!$this->ReadPropertyBoolean('EnableStatusList')) {
            return;
        }
        $this->WriteAttributeString('LastStatusList', $MonitoredVariableValues);
        $html = "<table style='width: 100%; border-collapse: collapse;'>";
        $html .= '<tr><td><b>Status</b></td><td><b>ID</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>Letzte Aktualisierung</b></td><td><b>Überfällig seit</b></td></tr>';
        //Sort variables by name
        array_multisort(array_column($variables, 'Name'), SORT_ASC, $variables);
        //Rebase array
        $variables = array_values($variables);
        $separator = false;
        if (!empty($variables)) {
            //Show variables with alarm first
            if ($this->ReadPropertyBoolean('EnableAlarm')) {
                foreach ($variables as $variable) {
                    $id = $variable['ID'];
                    if ($variable['ActualStatus'] == 1) {
                        $separator = true;
                        $html .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $id . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $variable['LastUpdate'] . '</td><td>' . $variable['OverdueSince'] . '</td></tr>';
                    }
                }
            }
            //Variables with no alarm are next
            if ($this->ReadPropertyBoolean('EnableOK')) {
                //Check if we have an active element for a spacer
                $existingElement = false;
                foreach ($variables as $variable) {
                    if ($variable['ActualStatus'] == 0) {
                        $existingElement = true;
                    }
                }
                //Add spacer
                if ($separator && $existingElement) {
                    $html .= '<tr><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td></tr>';
                }
                //Add variables
                foreach ($variables as $variable) {
                    $id = $variable['ID'];
                    if ($variable['ActualStatus'] == 0) {
                        $html .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $id . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $variable['LastUpdate'] . '</td><td>' . $variable['OverdueSince'] . '</td></tr>';
                    }
                }
            }
        }
        $html .= '</table>';
        $this->SetValue('StatusList', $html);
    }

    /**
     * Checks the overall status of all monitored variables.
     *
     * @param $MonitoredVariableValues
     * @return int
     * 0 =  OK,
     * 1 =  Alarm
     */

    /**
     * @param string $MonitoredVariableValues
     * @return int
     */
    private function CheckOverallStatus(string $MonitoredVariableValues): int
    {
        $overallStatus = 0;
        foreach (json_decode($MonitoredVariableValues, true) as $variable) {
            if ($variable['ActualStatus'] == 1) {
                $overallStatus = 1;
            }
        }
        return $overallStatus;
    }
}