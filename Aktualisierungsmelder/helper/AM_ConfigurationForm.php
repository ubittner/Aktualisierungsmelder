<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper
 * @file          AM_ConfigurationForm.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait AM_ConfigurationForm
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     *
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Modifies a trigger list configuration button
     *
     * @param string $Field
     * @param int $VariableID
     * @return void
     */
    public function ModifyTriggerListButton(string $Field, int $VariableID): void
    {
        $state = false;
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $VariableID . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $VariableID);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel1',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\t" . $module['ModuleName']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        //Status designations
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel2',
            'caption' => 'Statusbezeichnungen',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusTextAlarm',
                    'caption' => 'Bezeichnung für Alarm'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'StatusTextOK',
                    'caption' => 'Bezeichnung für OK'
                ]
            ]
        ];

        //Sensor list
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Listenoptionen',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [

                        [
                            'type'    => 'CheckBox',
                            'name'    => 'EnableAlarm'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'SensorListStatusTextAlarm',
                            'caption' => 'Alarm'
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'CheckBox',
                            'name'    => 'EnableOK'
                        ],
                        [
                            'type'    => 'ValidationTextBox',
                            'name'    => 'SensorListStatusTextOK',
                            'caption' => 'OK'
                        ]
                    ]
                ]
            ]
        ];

        //Trigger list
        $triggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $amount = count($variables);
        foreach ($variables as $variable) {
            $conditions = false;
            $variableLocation = '';
            $variableID = $variable['VariableID'];
            $lastUpdate = '';
            if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
                $variableLocation = IPS_GetLocation($variableID);
                $conditions = true;
            }
            $stateName = 'Existiert nicht!';
            $rowColor = '#FFC0C0'; //red
            if ($conditions) {
                $stateName = 'OK';
                $rowColor = '#C0FFC0'; //light green
                //Check update
                $lastUpdate = 'Nie';
                $variableUpdate = IPS_GetVariable($variableID)['VariableUpdated']; //timestamp or 0 = never
                if ($variableUpdate != 0) {
                    $lastUpdate = date('d.m.Y', $variableUpdate);
                }
                $now = time();
                $dateDifference = ($now - $variableUpdate) / (60 * 60 * 24);
                $updatePeriod = $variable['UpdatePeriod'];
                if ($dateDifference > $updatePeriod) {
                    $rowColor = '#FFC0C0'; //red
                    $stateName = 'Aktualisierung überfällig!';
                }
                if (!$variable['Use']) {
                    $stateName = 'Deaktiviert!';
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $triggerListValues[] = ['ActualStatus' => $stateName, 'ID' => $variableID, 'VariableID' => $variableID, 'VariableLocation' => $variableLocation, 'LastUpdate' => $lastUpdate, 'rowColor' => $rowColor];
        }

        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel4',
                'caption' => 'Auslöser',
                'items'   => [
                    [
                        'type'     => 'List',
                        'name'     => 'TriggerList',
                        'caption'  => 'Auslöser',
                        'rowCount' => $amount,
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'ActualStatus',
                            'direction' => 'ascending'
                        ],
                        'columns' => [
                            [
                                'caption' => 'Aktiviert',
                                'name'    => 'Use',
                                'width'   => '100px',
                                'add'     => true,
                                'edit'    => [
                                    'type' => 'CheckBox'
                                ]
                            ],
                            [
                                'name'    => 'ActualStatus',
                                'caption' => 'Aktueller Status',
                                'width'   => '200px',
                                'add'     => ''
                            ],
                            [
                                'name'    => 'ID',
                                'caption' => 'ID',
                                'width'   => '80px',
                                'add'     => '',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["ID"]);',
                            ],
                            [
                                'caption' => 'Objektbaum',
                                'name'    => 'VariableLocation',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["VariableID"]);',
                                'width'   => '350px',
                                'add'     => ''
                            ],
                            [
                                'caption' => 'Variable',
                                'name'    => 'VariableID',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["VariableID"]);',
                                'width'   => '300px',
                                'add'     => 0,
                                'visible' => false,
                                'edit'    => [
                                    'type' => 'SelectVariable'
                                ]
                            ],
                            [
                                'caption' => 'Name',
                                'name'    => 'Designation',
                                'width'   => '300px',
                                'add'     => '',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ]
                            ],
                            [
                                'caption' => 'Bemerkung',
                                'name'    => 'Comment',
                                'width'   => '300px',
                                'add'     => '',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ]
                            ],
                            [
                                'caption' => ' ',
                                'name'    => 'SpacerUpdatePeriod',
                                'width'   => '200px',
                                'add'     => '',
                                'visible' => false,
                                'edit'    => [
                                    'type' => 'Label'
                                ]
                            ],
                            [
                                'caption' => 'Bedingung:',
                                'name'    => 'LabelUpdatePeriod',
                                'width'   => '200px',
                                'add'     => '',
                                'visible' => false,
                                'edit'    => [
                                    'type'   => 'Label',
                                    'italic' => true,
                                    'bold'   => true
                                ]
                            ],
                            [
                                'caption' => 'Zeitraum',
                                'name'    => 'UpdatePeriod',
                                'width'   => '100px',
                                'add'     => 3,
                                'edit'    => [
                                    'type'   => 'NumberSpinner',
                                    'suffix' => ' Tage'
                                ]
                            ],
                            [
                                'name'    => 'LastUpdate',
                                'caption' => 'Letzte Aktualisierung',
                                'width'   => '190px',
                                'add'     => ''
                            ]
                        ],
                        'values' => $triggerListValues,
                    ],
                    [
                        'type'     => 'OpenObjectButton',
                        'name'     => 'TriggerListConfigurationButton',
                        'caption'  => 'Bearbeiten',
                        'visible'  => false,
                        'objectID' => 0
                    ]
                ]
            ];

        ##### Automatic status update
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel5',
            'caption' => 'Aktualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'AutomaticStatusUpdate',
                    'caption' => 'Automatische Aktualisierung'
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'StatusUpdateInterval',
                    'caption' => 'Intervall',
                    'suffix'  => 'Sekunden'
                ]
            ]
        ];

        ##### Notifications

        ### Notification Alarm

        //Notification
        $notificationAlarmValues = [];
        $elements = json_decode($this->ReadPropertyString('NotificationAlarm'), true);
        $amountNotificationAlarm = count($elements);
        if ($amountNotificationAlarm == 0) {
            $amountNotificationAlarm = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $notificationAlarmValues[] = ['rowColor' => $rowColor];
        }

        //Push notification
        $pushNotificationAlarmValues = [];
        $elements = json_decode($this->ReadPropertyString('PushNotificationAlarm'), true);
        $amountPushNotificationAlarm = count($elements);
        if ($amountPushNotificationAlarm == 0) {
            $amountPushNotificationAlarm = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $pushNotificationAlarmValues[] = ['rowColor' => $rowColor];
        }

        //Mailer
        $mailerNotificationAlarmValues = [];
        $elements = json_decode($this->ReadPropertyString('MailerNotificationAlarm'), true);
        $amountMailerNotificationAlarm = count($elements);
        if ($amountMailerNotificationAlarm == 0) {
            $amountMailerNotificationAlarm = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $mailerNotificationAlarmValues[] = ['rowColor' => $rowColor];
        }

        ### Notification OK

        //Notification
        $notificationValues = [];
        $elements = json_decode($this->ReadPropertyString('Notification'), true);
        $amountNotification = count($elements);
        if ($amountNotification == 0) {
            $amountNotification = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $notificationValues[] = ['rowColor' => $rowColor];
        }

        //Push notification
        $pushNotificationValues = [];
        $elements = json_decode($this->ReadPropertyString('PushNotification'), true);
        $amountPushNotification = count($elements);
        if ($amountPushNotification == 0) {
            $amountPushNotification = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $pushNotificationValues[] = ['rowColor' => $rowColor];
        }

        //Mailer
        $mailerNotificationValues = [];
        $elements = json_decode($this->ReadPropertyString('MailerNotification'), true);
        $amountMailerNotification = count($elements);
        if ($amountMailerNotification == 0) {
            $amountMailerNotification = 1;
        }
        foreach ($elements as $element) {
            $rowColor = '#FFC0C0'; //red
            $id = $element['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $rowColor = '#C0FFC0'; //light green
                if (!$element['Use']) {
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $mailerNotificationValues[] = ['rowColor' => $rowColor];
        }

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel6',
            'caption' => 'Benachrichtigungen',
            'items'   => [

                ### Alarm

                [
                    'type'     => 'List',
                    'name'     => 'NotificationAlarm',
                    'caption'  => 'Nachricht Alarm',
                    'rowCount' => $amountNotificationAlarm,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "NotificationAlarmConfigurationButton", "ID " . $NotificationAlarm["ID"] . " konfigurieren", $NotificationAlarm["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'Icon',
                            'width'   => '200px',
                            'add'     => 'Warning',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🔴  %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'DisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $notificationAlarmValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'NotificationAlarmConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'PushNotificationAlarm',
                    'caption'  => 'Push-Nachricht Alarm',
                    'rowCount' => $amountPushNotificationAlarm,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "PushNotificationAlarmConfigurationButton", "ID " . $PushNotificationAlarm["ID"] . " konfigurieren", $PushNotificationAlarm["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🔴  %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
                            'width'   => '200px',
                            'add'     => 'alarm',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'TargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $pushNotificationAlarmValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'PushNotificationAlarmConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'MailerNotificationAlarm',
                    'caption'  => 'E-Mail Alarm',
                    'rowCount' => $amountMailerNotificationAlarm,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "MailerNotificationAlarmConfigurationButton", "ID " . $MailerNotificationAlarm["ID"] . " konfigurieren", $MailerNotificationAlarm["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🔴  %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitraum',
                            'name'    => 'UseUpdatePeriod',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Letzte Aktualisierung',
                            'name'    => 'UseLastUpdate',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $mailerNotificationAlarmValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'MailerNotificationAlarmConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' ',
                ],

                ### OK

                [
                    'type'     => 'List',
                    'name'     => 'Notification',
                    'caption'  => 'Nachricht OK',
                    'rowCount' => $amountNotification,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "NotificationConfigurationButton", "ID " . $Notification["ID"] . " konfigurieren", $Notification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Icon',
                            'name'    => 'Icon',
                            'width'   => '200px',
                            'add'     => 'Ok',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectIcon'
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🟢  %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'DisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => 'Sekunden'
                            ]
                        ]
                    ],
                    'values' => $notificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'NotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'PushNotification',
                    'caption'  => 'Push-Nachricht OK',
                    'rowCount' => $amountPushNotification,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'WebFront',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "PushNotificationConfigurationButton", "ID " . $PushNotification["ID"] . " konfigurieren", $PushNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::WEBFRONT_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Titel der Meldung (maximal 32 Zeichen)',
                            'name'    => 'Title',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🟢  %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Standard',
                                        'value'   => ''
                                    ],
                                    [
                                        'caption' => 'Alarm',
                                        'value'   => 'alarm'
                                    ],
                                    [
                                        'caption' => 'Bell',
                                        'value'   => 'bell'
                                    ],
                                    [
                                        'caption' => 'Boom',
                                        'value'   => 'boom'
                                    ],
                                    [
                                        'caption' => 'Buzzer',
                                        'value'   => 'buzzer'
                                    ],
                                    [
                                        'caption' => 'Connected',
                                        'value'   => 'connected'
                                    ],
                                    [
                                        'caption' => 'Dark',
                                        'value'   => 'dark'
                                    ],
                                    [
                                        'caption' => 'Digital',
                                        'value'   => 'digital'
                                    ],
                                    [
                                        'caption' => 'Drums',
                                        'value'   => 'drums'
                                    ],
                                    [
                                        'caption' => 'Duck',
                                        'value'   => 'duck'
                                    ],
                                    [
                                        'caption' => 'Full',
                                        'value'   => 'full'
                                    ],
                                    [
                                        'caption' => 'Happy',
                                        'value'   => 'happy'
                                    ],
                                    [
                                        'caption' => 'Horn',
                                        'value'   => 'horn'
                                    ],
                                    [
                                        'caption' => 'Inception',
                                        'value'   => 'inception'
                                    ],
                                    [
                                        'caption' => 'Kazoo',
                                        'value'   => 'kazoo'
                                    ],
                                    [
                                        'caption' => 'Roll',
                                        'value'   => 'roll'
                                    ],
                                    [
                                        'caption' => 'Siren',
                                        'value'   => 'siren'
                                    ],
                                    [
                                        'caption' => 'Space',
                                        'value'   => 'space'
                                    ],
                                    [
                                        'caption' => 'Trickling',
                                        'value'   => 'trickling'
                                    ],
                                    [
                                        'caption' => 'Turn',
                                        'value'   => 'turn'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'caption' => 'Zielscript',
                            'name'    => 'TargetID',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $pushNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'PushNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "WebFront");'
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'     => 'List',
                    'name'     => 'MailerNotification',
                    'caption'  => 'E-Mail OK',
                    'rowCount' => $amountMailerNotification,
                    'add'      => true,
                    'delete'   => true,
                    'columns'  => [
                        [
                            'caption' => 'Aktiviert',
                            'name'    => 'Use',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Mailer',
                            'name'    => 'ID',
                            'width'   => '300px',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "MailerNotificationConfigurationButton", "ID " . $MailerNotification["ID"] . " konfigurieren", $MailerNotification["ID"]);',
                            'edit'    => [
                                'type'     => 'SelectModule',
                                'moduleID' => self::MAILER_MODULE_GUID
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Betreff',
                            'name'    => 'Subject',
                            'width'   => '350px',
                            'add'     => 'Aktualisierungsmelder',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🟢  %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitraum',
                            'name'    => 'UseUpdatePeriod',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Letzte Aktualisierung',
                            'name'    => 'UseLastUpdate',
                            'width'   => '100px',
                            'add'     => true,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $mailerNotificationValues,
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'MailerNotificationConfigurationButton',
                            'caption'  => 'Bearbeiten',
                            'visible'  => false,
                            'objectID' => 0
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateInstance($id, "Mailer");'
                        ]
                    ]
                ]
            ]
        ];

        ##### Visualisation

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel7',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableStatus',
                    'caption' => 'Status'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableTriggeringDetector',
                    'caption' => 'Auslösender Melder'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLastUpdate',
                    'caption' => 'Letzte Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateStatus',
                    'caption' => 'Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableAlarmSensorList',
                    'caption' => 'Aktualisierungsmelderliste'
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Auslöser'
            ];

        $form['actions'][] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Variablen ermitteln',
                        'popup'   => [
                            'caption' => 'Variablen wirklich automatisch ermitteln und hinzufügen?',
                            'items'   => [
                                [
                                    'type'    => 'Select',
                                    'name'    => 'VariableDeterminationType',
                                    'caption' => 'Auswahl',
                                    'options' => [
                                        [
                                            'caption' => 'Profil auswählen',
                                            'value'   => 0
                                        ],
                                        [
                                            'caption' => 'Profil: ~Battery',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => 'Profil: ~Battery.Reversed',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => 'Profil: BATM.Battery.Boolean',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => 'Profil: BATM.Battery.Boolean.Reversed',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => 'Profil: BATM.Battery.Integer',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => 'Profil: BATM.Battery.Integer.Reversed',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => 'Ident: LOWBAT',
                                            'value'   => 7
                                        ],
                                        [
                                            'caption' => 'Ident: LOW_BAT',
                                            'value'   => 8
                                        ],
                                        [
                                            'caption' => 'Ident: LOWBAT, LOW_BAT',
                                            'value'   => 9
                                        ],
                                        [
                                            'caption' => 'Ident: Benutzerdefiniert',
                                            'value'   => 10
                                        ]
                                    ],
                                    'value'    => 9,
                                    'onChange' => self::MODULE_PREFIX . '_CheckVariableDeterminationValue($id, $VariableDeterminationType);'
                                ],
                                [
                                    'type'    => 'SelectProfile',
                                    'name'    => 'ProfileSelection',
                                    'caption' => 'Profil',
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'ValidationTextBox',
                                    'name'    => 'VariableDeterminationValue',
                                    'caption' => 'Identifikator',
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Ermitteln',
                                    'onClick' => self::MODULE_PREFIX . '_DetermineVariables($id, $VariableDeterminationType, $VariableDeterminationValue, $ProfileSelection);'
                                ],
                                [
                                    'type'    => 'ProgressBar',
                                    'name'    => 'VariableDeterminationProgress',
                                    'caption' => 'Fortschritt',
                                    'minimum' => 0,
                                    'maximum' => 100,
                                    'visible' => false
                                ],
                                [
                                    'type'    => 'Label',
                                    'name'    => 'VariableDeterminationProgressInfo',
                                    'caption' => '',
                                    'visible' => false
                                ],
                                [
                                    'type'     => 'List',
                                    'name'     => 'DeterminedVariableList',
                                    'caption'  => 'Variablen',
                                    'visible'  => false,
                                    'rowCount' => 15,
                                    'delete'   => true,
                                    'sort'     => [
                                        'column'    => 'ID',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'caption' => 'Übernehmen',
                                            'name'    => 'Use',
                                            'width'   => '100px',
                                            'add'     => true,
                                            'edit'    => [
                                                'type' => 'CheckBox'
                                            ]
                                        ],
                                        [
                                            'name'    => 'ID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'add'     => ''
                                        ],
                                        [
                                            'caption' => 'Objektbaum',
                                            'name'    => 'Location',
                                            'width'   => '800px',
                                            'add'     => ''
                                        ],
                                    ]
                                ],
                                [
                                    'type'    => 'Button',
                                    'name'    => 'ApplyPreTriggerValues',
                                    'caption' => 'Übernehmen',
                                    'visible' => false,
                                    'onClick' => self::MODULE_PREFIX . '_ApplyDeterminedVariables($id, $DeterminedVariableList);'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Status aktualisieren',
                        'popup'   => [
                            'caption' => 'Status wirklich aktualisieren?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Aktualisieren',
                                    'onClick' => self::MODULE_PREFIX . '_UpdateStatus($id);' . self::MODULE_PREFIX . '_UIShowMessage($id, "Status wurde aktualisiert!");'
                                ]
                            ],
                            'buttons' => [
                                [
                                    'caption' => 'Konfiguration neu laden',
                                    'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Kritische Variablen zurücksetzen',
                        'popup'   => [
                            'caption' => 'Kritische Variablen wirklich zurücksetzen?',
                            'items'   => [
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Zurücksetzen',
                                    'onClick' => self::MODULE_PREFIX . '_ResetCriticalVariables($id); ' . self::MODULE_PREFIX . '_UIShowMessage($id, "Variablen wurden erfolgreich zurückgesetzt!");'
                                ]
                            ],
                            'buttons' => [
                                [
                                    'caption' => 'Konfiguration neu laden',
                                    'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                                ]
                            ]
                        ]
                    ]
                ]
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => 'Schaltelemente'
            ];

        //Test center
        $form['actions'][] =
            [
                'type' => 'TestCenter',
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Critical variables
        $criticalVariables = [];
        foreach (json_decode($this->ReadAttributeString('CriticalVariables'), true) as $criticalVariable) {
            $name = 'Objekt #' . $criticalVariable . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($criticalVariable)) {
                $name = IPS_GetName($criticalVariable);
                $rowColor = '#FFFFC0'; //yellow
            }
            $criticalVariables[] = [
                'ObjectID' => $criticalVariable,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }
        $amountCriticalVariables = count($criticalVariables);
        if ($amountCriticalVariables == 0) {
            $amountCriticalVariables = 1;
        }

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        //Developer area
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Auslöser',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Verknüpfung erstellen',
                            'popup'   => [
                                'caption' => 'Variablenverknüpfungen wirklich erstellen?',
                                'items'   => [
                                    [
                                        'type'    => 'SelectCategory',
                                        'name'    => 'LinkCategory',
                                        'caption' => 'Kategorie',
                                        'width'   => '610px'
                                    ],
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Erstellen',
                                        'onClick' => self::MODULE_PREFIX . '_CreateVariableLinks($id, $LinkCategory);'
                                    ],
                                    [
                                        'type'    => 'ProgressBar',
                                        'name'    => 'VariableLinkProgress',
                                        'caption' => 'Fortschritt',
                                        'minimum' => 0,
                                        'maximum' => 100,
                                        'visible' => false
                                    ],
                                    [
                                        'type'    => 'Label',
                                        'name'    => 'VariableLinkProgressInfo',
                                        'caption' => '',
                                        'visible' => false
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Kritische Variablen',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'CriticalVariables',
                    'delete'   => true,
                    'onDelete' => self::MODULE_PREFIX . '_DeleteElementFromAttribute($id, "CriticalVariables", $CriticalVariables["ObjectID"]);',
                    'rowCount' => $amountCriticalVariables,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "CriticalVariablesConfigurationButton", "ID " . $CriticalVariables["ObjectID"] . " aufrufen", $CriticalVariables["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "CriticalVariablesConfigurationButton", "ID " . $CriticalVariables["ObjectID"] . " aufrufen", $CriticalVariables["ObjectID"]);'
                        ]
                    ],
                    'values' => $criticalVariables
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'CriticalVariablesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => $amountReferences,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Nachrichten',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => $amountMessages,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => $module['ModuleName'] . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}