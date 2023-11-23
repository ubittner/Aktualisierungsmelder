<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper
 * @file          AM_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

trait AM_MonitoredVariables
{
    /**
     * Applies the determined variables to the trigger list.
     *
     * @param object $ListValues
     * false =  don't overwrite
     * true =   overwrite
     *
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function ApplyDeterminedVariables(object $ListValues): void
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
                'Use'                => true,
                'VariableID'         => $id,
                'Designation'        => $name,
                'Comment'            => $address,
                'UpdatePeriod'       => 3];
        }
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            $determinedVariableID = $determinedVariable['VariableID'];
            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                //Check variable id with already listed variable ids
                $add = true;
                foreach ($listedVariables as $listedVariable) {
                    $listedVariableID = $listedVariable['VariableID'];
                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
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
        if ($VariableDeterminationType == 10) {
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
                case 0: //Profile: Select profile
                    if ($ProfileSelection == '') {
                        $infoText = 'Abbruch, es wurde kein Profil ausgewählt!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineProfile = true;
                    }
                    break;

                case 1: //Profile: ~Battery
                case 2: //Profile: ~Battery.Reversed
                case 3: //Profile: BATM.Battery.Boolean
                case 4: //Profile: BATM.Battery.Boolean.Reversed
                case 5: //Profile: BATM.Battery.Integer
                case 6: //Profile: BATM.Battery.Integer.reversed
                    $determineProfile = true;
                    break;

                case 7: //Ident: LOWBAT
                case 8: //Ident: LOW_BAT
                case 9: //Ident: LOWBAT, LOW_BAT
                    $determineIdent = true;
                    break;

                case 10: //Custom Ident
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

            ##### Profile

            //Determine via profile
            if ($determineProfile && !$determineIdent) {
                switch ($DeterminationType) {

                    case 0: //Select profile
                        $profileNames = $ProfileSelection;
                        break;

                    case 1:
                        $profileNames = '~Battery';
                        break;

                    case 2:
                        $profileNames = '~Battery.Reversed';
                        break;

                    case 3:
                        $profileNames = 'BATM.Battery.Boolean';
                        break;

                    case 4:
                        $profileNames = 'BATM.Battery.Boolean.Reversed';
                        break;

                    case 5:
                        $profileNames = 'BATM.Battery.Integer';
                        break;

                    case 6:
                        $profileNames = 'BATM.Battery.Integer.Reversed';
                        break;

                }
                if (isset($profileNames)) {
                    $profileNames = str_replace(' ', '', $profileNames);
                    $profileNames = explode(',', $profileNames);
                    foreach ($profileNames as $profileName) {
                        $variableData = IPS_GetVariable($variable);
                        if ($variableData['VariableCustomProfile'] == $profileName || $variableData['VariableProfile'] == $profileName) {
                            $location = @IPS_GetLocation($variable);
                            $determinedVariables[] = [
                                'Use'      => true,
                                'ID'       => $variable,
                                'Location' => $location];
                        }
                    }
                }
            }

            ##### Ident

            //Determine via ident
            if ($determineIdent && !$determineProfile) {
                switch ($DeterminationType) {
                    case 7:
                        $objectIdents = 'LOWBAT';
                        break;

                    case 8:
                        $objectIdents = 'LOW_BAT';
                        break;

                    case 9:
                        $objectIdents = 'LOWBAT, LOW_BAT';
                        break;

                    case 10: //Custom ident
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
                                'Use'      => true,
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
            $listedVariableID = $listedVariable['VariableID'];
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
        $this->UpdateFormField('DeterminedVariableList', 'visible', true);
        $this->UpdateFormField('DeterminedVariableList', 'rowCount', count($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'values', json_encode($determinedVariables));
        $this->UpdateFormField('OverwriteVariableProfiles', 'visible', true);
        $this->UpdateFormField('ApplyPreTriggerValues', 'visible', true);
    }

    /**
     * Creates links of monitored variables.
     *
     * @param int $LinkCategory
     * @return void
     * @throws Exception
     */
    public function CreateVariableLinks(int $LinkCategory): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            $this->UIShowMessage('Abbruch, bitte wählen Sie eine Kategorie aus!');
            return;
        }
        //Get all monitored variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $maximumVariables = count($monitoredVariables);
        $this->UpdateFormField('VariableLinkProgress', 'minimum', 0);
        $this->UpdateFormField('VariableLinkProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        $targetIDs = [];
        $i = 0;
        foreach ($monitoredVariables as $variable) {
            if ($variable['Use']) {
                $passedVariables++;
                $this->UpdateFormField('VariableLinkProgress', 'visible', true);
                $this->UpdateFormField('VariableLinkProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableLinkProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableLinkProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                $id = $variable['VariableID'];
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
        $this->UIShowMessage('Die Variablenverknüpfungen wurden erfolgreich erstellt!');
    }

    /**
     * Restes the attribute for critical variables.
     *
     * @return void
     * @throws Exception
     */
    public function ResetCriticalVariables(): void
    {
        $this->WriteAttributeString('CriticalVariables', '[]');
    }

    /**
     * Updates the status.
     *
     * @return bool
     * false    = OK
     * true     = Alarm
     *
     * @throws Exception
     */
    public function UpdateStatus(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        //Enter semaphore
        if (!$this->LockSemaphore('Update')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('Update');
            return false;
        }
        if (!$this->CheckForExistingVariables()) {
            $this->UnlockSemaphore('Update');
            return false;
        }

        ##### Update overall status

        $variables = json_decode($this->GetMonitoredVariables(), true);
        $actualOverallStatus = false;
        foreach ($variables as $variable) {
            if ($variable['ActualStatus'] == 1) { //1 = alarm
                $actualOverallStatus = true;
            }
        }
        if ($this->GetValue('Status') != $actualOverallStatus) {
            $this->SetValue('Status', $actualOverallStatus);
        }

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Update overview list for WebFront

        $string = '';
        if ($this->ReadPropertyBoolean('EnableAlarmSensorList')) {
            $string .= "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>ID</b></td><td><b>Zeitraum</b></td><td><b>Letzte Aktualisierung</b></td></tr>';
            //Sort variables by name
            array_multisort(array_column($variables, 'Name'), SORT_ASC, $variables);
            //Rebase array
            $variables = array_values($variables);
            $separator = false;
            if (!empty($variables)) {
                //Show sensors with alarm first
                if ($this->ReadPropertyBoolean('EnableAlarm')) {
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 1) {
                                $separator = true;
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td><td>' . $variable['UpdatePeriod'] . ' Tage</td><td>' . $variable['LastUpdate'] . '</td></tr>';
                            }
                        }
                    }
                }
                //Sensors with no alarm are next
                if ($this->ReadPropertyBoolean('EnableOK')) {
                    //Check if we have an active element for a spacer
                    $existingElement = false;
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $existingElement = true;
                            }
                        }
                    }
                    //Add spacer
                    if ($separator && $existingElement) {
                        $string .= '<tr><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td><td><b>&#8205;</b></td></tr>';
                    }
                    //Add sensors
                    foreach ($variables as $variable) {
                        $id = $variable['ID'];
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            if ($variable['ActualStatus'] == 0) {
                                $string .= '<tr><td>' . $variable['StatusText'] . '</td><td>' . $variable['Name'] . '</td><td>' . $variable['Comment'] . '</td><td>' . $id . '</td><td>' . $variable['UpdatePeriod'] . ' Tage</td><td>' . $variable['LastUpdate'] . '</td></tr>';
                            }
                        }
                    }
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('AlarmSensorList', $string);

        ##### Last triggering detector

        $triggeringDetectorName = '';
        foreach ($variables as $variable) {
            $id = $variable['ID'];
            if ($id != 0 && IPS_ObjectExists($id)) {
                if ($variable['ActualStatus'] == 1) {
                    $triggeringDetectorName = $variable['Name'];
                }
            }
        }
        if ($this->GetValue('TriggeringDetector') != $triggeringDetectorName) {
            $this->SetValue('TriggeringDetector', $triggeringDetectorName);
        }

        ##### Notification

        $criticalVariables = json_decode($this->ReadAttributeString('CriticalVariables'), true);

        foreach ($variables as $variable) {
            $id = $variable['ID'];
            if ($id != 0 && IPS_ObjectExists($id)) {

                //Alarm
                if ($variable['ActualStatus'] == 1) {
                    if (!in_array($id, $criticalVariables)) {
                        //Add to critical variables
                        $criticalVariables[] = $id;
                        //Notification
                        $this->SendNotification(1, $variable['Name']);
                        //Push notification
                        $this->SendPushNotification(1, $variable['Name']);
                        //Mailer notification
                        $this->SendMailerNotification(1, $id); //change to id
                    }
                }

                //OK
                if ($variable['ActualStatus'] == 0) {
                    if (in_array($id, $criticalVariables)) {
                        //Remove from critical variables
                        $criticalVariables = array_diff($criticalVariables, [$id]);
                        //Notification
                        $this->SendNotification(0, $variable['Name']);
                        //Push notification
                        $this->SendPushNotification(0, $variable['Name']);
                        //Mailer notification
                        $this->SendMailerNotification(0, $id); //change to id
                    }
                }
            }
        }
        $this->WriteAttributeString('CriticalVariables', json_encode(array_values($criticalVariables)));
        //Leave semaphore
        $this->UnlockSemaphore('Update');
        return $actualOverallStatus;
    }

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    public function GetMonitoredVariables(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['VariableID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0; //OK
                $statusText = $this->ReadPropertyString('SensorListStatusTextOK');
                //Check for update overdue
                $variableUpdate = IPS_GetVariable($id)['VariableUpdated']; //timestamp or 0 = never
                $lastUpdate = date('d.m.Y H:i:s', $variableUpdate);
                $now = time();
                $dateDifference = ($now - $variableUpdate) / (60 * 60 * 24);
                $updatePeriod = $variable['UpdatePeriod'];
                if ($dateDifference > $updatePeriod) {
                    $actualStatus = 1;
                    $statusText = $this->ReadPropertyString('SensorListStatusTextAlarm');
                }
                $result[] = [
                    'ID'           => $id,
                    'Name'         => $variable['Designation'],
                    'Comment'      => $variable['Comment'],
                    'UpdatePeriod' => $variable['UpdatePeriod'], //in days
                    'LastUpdate'   => $lastUpdate,
                    'ActualStatus' => $actualStatus,
                    'StatusText'   => $statusText];
            }
        }
        if (!empty($result)) {
            //Sort variables by name
            array_multisort(array_column($result, 'Name'), SORT_ASC, $result);
        }
        return json_encode($result);
    }

    #################### Private

    /**
     * Checks for monitored variables.
     *
     * @return bool
     * false =  There are no monitored variables
     * true =   There are monitored variables
     * @throws Exception
     */
    private function CheckForExistingVariables(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['VariableID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                return true;
            }
        }
        $this->SendDebug(__FUNCTION__, 'Abbruch, Es werden keine Variablen überwacht!', 0);
        return false;
    }
}