<?php

/**
 * @project       Aktualisierungsmelder/Aktualisierungsmelder/helper/
 * @file          AM_ConfigurationForm.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
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
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
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
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $VariableID . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $VariableID);
    }

    public function ModifyActualVariableStatesVariableButton(string $Field, int $VariableID): void
    {
        $state = false;
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) {
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

        //List options
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel3',
            'caption' => 'Listenoptionen',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [

                        [
                            'type' => 'CheckBox',
                            'name' => 'EnableAlarm'
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
                            'type' => 'CheckBox',
                            'name' => 'EnableOK'
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

        //Update period
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel4',
            'caption' => 'Aktualisierungszeitraum',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'NumberSpinner',
                            'name'    => 'TimeValue',
                            'caption' => 'Zeit',
                            'minimum' => 1,
                            'maximum' => 30240
                        ],
                        [
                            'type'     => 'Select',
                            'name'     => 'TimeBase',
                            'caption'  => 'Einheit',
                            'onChange' => self::MODULE_PREFIX . '_CheckTimeValue($id, $TimeBase);',
                            'options'  => [
                                [
                                    'caption' => 'Sekunden',
                                    'value'   => 0
                                ],
                                [
                                    'caption' => 'Minuten',
                                    'value'   => 1
                                ],
                                [
                                    'caption' => 'Stunden',
                                    'value'   => 2
                                ],
                                [
                                    'caption' => 'Tage',
                                    'value'   => 3
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'StartUpCheckMode',
                    'caption' => 'Verhalten bei Neustart',
                    'width'   => '607px',
                    'options' => [
                        [
                            'caption' => 'Sofortige Prüfung',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Prüfung zum nächsten Prüfzeitpunkt',
                            'value'   => 1
                        ]
                    ]
                ]
            ]
        ];

        //Monitored variables
        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel5',
                'caption' => 'Auslöser',
                'items'   => [
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
                                            'caption' => 'Ident: LOWBAT',
                                            'value'   => 1
                                        ],
                                        [
                                            'caption' => 'Ident: LOW_BAT',
                                            'value'   => 2
                                        ],
                                        [
                                            'caption' => 'Ident: LOWBAT, LOW_BAT',
                                            'value'   => 3
                                        ],
                                        [
                                            'caption' => 'Ident: STATE',
                                            'value'   => 4
                                        ],
                                        [
                                            'caption' => 'Ident: MOTION',
                                            'value'   => 5
                                        ],
                                        [
                                            'caption' => 'Ident: SMOKE_DETECTOR_ALARM_STATUS',
                                            'value'   => 6
                                        ],
                                        [
                                            'caption' => 'Ident: ALARMSTATE',
                                            'value'   => 7
                                        ],
                                        [
                                            'caption' => 'Ident: Benutzerdefiniert',
                                            'value'   => 8
                                        ]
                                    ],
                                    'value'    => 0,
                                    'onChange' => self::MODULE_PREFIX . '_CheckVariableDeterminationValue($id, $VariableDeterminationType);'
                                ],
                                [
                                    'type'    => 'SelectProfile',
                                    'name'    => 'ProfileSelection',
                                    'caption' => 'Profil',
                                    'visible' => true
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
                                    'rowCount' => 1,
                                    'delete'   => true,
                                    'visible'  => false,
                                    'sort'     => [
                                        'column'    => 'Location',
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
                                    'onClick' => self::MODULE_PREFIX . '_AddSelectedVariables($id, $DeterminedVariableList);'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'    => 'PopupButton',
                        'caption' => 'Aktueller Status',
                        'popup'   => [
                            'caption' => 'Aktueller Status',
                            'items'   => [
                                [
                                    'type'     => 'List',
                                    'name'     => 'ActualVariableStates',
                                    'caption'  => 'Variablen',
                                    'add'      => false,
                                    'visible'  => false,
                                    'rowCount' => 1,
                                    'sort'     => [
                                        'column'    => 'ActualStatus',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'name'    => 'ActualStatus',
                                            'caption' => 'Aktueller Status',
                                            'width'   => '300px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'ID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'onClick' => self::MODULE_PREFIX . '_ModifyActualVariableStatesVariableButton($id, "ActualVariableStatesConfigurationButton", $ActualVariableStates["ID"]);',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Designation',
                                            'caption' => 'Name',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Comment',
                                            'caption' => 'Bemerkung',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'LastUpdate',
                                            'caption' => 'Letzte Aktualisierung',
                                            'width'   => '200px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'OverdueSince',
                                            'caption' => 'Überfällig seit',
                                            'width'   => '200px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'UpdatePeriod',
                                            'caption' => 'Letzter Aktualisierungszeitraum',
                                            'width'   => '260px',
                                            'save'    => false
                                        ]
                                    ]
                                ],
                                [
                                    'type'     => 'OpenObjectButton',
                                    'name'     => 'ActualVariableStatesConfigurationButton',
                                    'caption'  => 'Bearbeiten',
                                    'visible'  => false,
                                    'objectID' => 0
                                ]
                            ]
                        ],
                        'onClick' => self::MODULE_PREFIX . '_ListActualVariableStates($id);'
                    ],
                    [
                        'type'     => 'List',
                        'name'     => 'TriggerList',
                        'caption'  => 'Auslöser',
                        'rowCount' => $this->CountRows('TriggerList'),
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'Designation',
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
                                'caption' => 'Name',
                                'name'    => 'Designation',
                                'width'   => '400px',
                                'add'     => '',
                                'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $TriggerList["ID"]);',
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
                                'caption' => 'Variable',
                                'name'    => 'ID',
                                'width'   => '800px',
                                'add'     => 0,
                                'edit'    => [
                                    'type' => 'SelectVariable'
                                ]
                            ]
                        ],
                        'values' => $this->GetListValues('TriggerList'),
                    ],
                    [
                        'type'    => 'Label',
                        'caption' => 'Anzahl Auslöser: ' . $this->CountElements('TriggerList')
                    ],
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
                                    'type'     => 'List',
                                    'name'     => 'VariableLinkList',
                                    'caption'  => 'Variablen',
                                    'add'      => false,
                                    'rowCount' => $this->CountElements('TriggerList'),
                                    'sort'     => [
                                        'column'    => 'Designation',
                                        'direction' => 'ascending'
                                    ],
                                    'columns' => [
                                        [
                                            'caption' => 'Auswahl',
                                            'name'    => 'Use',
                                            'width'   => '100px',
                                            'add'     => false,
                                            'edit'    => [
                                                'type' => 'CheckBox'
                                            ]
                                        ],
                                        [
                                            'name'    => 'SensorID',
                                            'caption' => 'ID',
                                            'width'   => '80px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Designation',
                                            'caption' => 'Name',
                                            'width'   => '400px',
                                            'save'    => false
                                        ],
                                        [
                                            'name'    => 'Comment',
                                            'caption' => 'Bemerkung',
                                            'width'   => '400px',
                                            'save'    => false
                                        ]
                                    ],
                                    'values' => $this->GetListValues('VariableLinkList'),
                                ],
                                [
                                    'type'    => 'Button',
                                    'caption' => 'Erstellen',
                                    'onClick' => self::MODULE_PREFIX . '_CreateVariableLinks($id, $LinkCategory, $VariableLinkList);'
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

        //Notifications
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel6',
            'caption' => 'Benachrichtigungen',
            'items'   => [
                ### Alarm
                [
                    'type'    => 'Label',
                    'caption' => 'Alarm',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'NotificationAlarm',
                    'caption'  => 'Nachricht Alarm',
                    'rowCount' => $this->CountRows('NotificationAlarm'),
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
                            'add'     => '🔴 %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'DisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => ' Sekunden'
                            ]
                        ]
                    ],
                    'values' => $this->GetListValues('NotificationAlarm'),
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
                    'rowCount' => $this->CountRows('PushNotificationAlarm'),
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
                            'add'     => '🔴 %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
                            'width'   => '200px',
                            'add'     => 'alarm',
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
                            'width'   => '600px',
                            'add'     => 0,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $this->GetListValues('PushNotificationAlarm'),
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
                    'rowCount' => $this->CountRows('MailerNotificationAlarm'),
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
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🔴 %1$s, Aktualisierung überfällig!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Letzte Aktualisierung',
                            'name'    => 'UseLastUpdate',
                            'width'   => '200px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Überfällig seit',
                            'name'    => 'UseOverdueSince',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ]
                    ],
                    'values' => $this->GetListValues('MailerNotificationAlarm'),
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
                    'type'    => 'Label',
                    'caption' => 'OK',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'Notification',
                    'caption'  => 'Nachricht OK',
                    'rowCount' => $this->CountRows('Notification'),
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
                            'add'     => '🟢 %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Anzeigedauer',
                            'name'    => 'DisplayDuration',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'   => 'NumberSpinner',
                                'suffix' => ' Sekunden'
                            ]
                        ]
                    ],
                    'values' => $this->GetListValues('Notification')
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
                    'rowCount' => $this->CountRows('PushNotification'),
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
                            'add'     => '🟢 %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Zeitstempel',
                            'name'    => 'UseTimestamp',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Sound',
                            'name'    => 'Sound',
                            'width'   => '200px',
                            'add'     => '',
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
                            'width'   => '600px',
                            'add'     => 0,
                            'edit'    => [
                                'type' => 'SelectScript'
                            ]
                        ]
                    ],
                    'values' => $this->GetListValues('PushNotification')
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
                    'rowCount' => $this->CountRows('MailerNotification'),
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
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Text der Meldung (maximal 256 Zeichen)',
                            'name'    => 'Text',
                            'width'   => '350px',
                            'add'     => '🟢 %1$s, hat sich wieder aktualisiert!',
                            'edit'    => [
                                'type'      => 'ValidationTextBox',
                                'multiline' => true
                            ]
                        ],
                        [
                            'caption' => 'Letzte Aktualisierung',
                            'name'    => 'UseLastUpdate',
                            'width'   => '200px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => 'Überfällig seit',
                            'name'    => 'UseOverdueSince',
                            'width'   => '150px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                    ],
                    'values' => $this->GetListValues('MailerNotification')
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

        //Visualisation
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
                    'name'    => 'EnableLastCheck',
                    'caption' => 'Letzte Überprüfung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableUpdateStatus',
                    'caption' => 'Aktualisierung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableStatusList',
                    'caption' => 'Statusliste'
                ]
            ]
        ];

        ########## Actions

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

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        $amountReferences = count($references);
        if ($amountReferences == 0) {
            $amountReferences = 3;
        }
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $location = IPS_GetLocation($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID'         => $reference,
                'Name'             => $name,
                'VariableLocation' => $location,
                'rowColor'         => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        $amountMessages = count($messages);
        if ($amountMessages == 0) {
            $amountMessages = 3;
        }
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $location = '';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $location = IPS_GetLocation($id);
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
                'VariableLocation'   => $location,
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
                    'caption' => 'Kritische Auslöser',
                    'italic'  => true,
                    'bold'    => true
                ],
                [
                    'type'    => 'PopupButton',
                    'caption' => 'Aktueller Status',
                    'popup'   => [
                        'caption' => 'Aktueller Status',
                        'items'   => [
                            [
                                'type'     => 'List',
                                'name'     => 'CriticalVariableList',
                                'caption'  => 'Kritische Auslöser',
                                'rowCount' => 1,
                                'add'      => false,
                                'delete'   => true,
                                'onDelete' => self::MODULE_PREFIX . '_DeleteVariableFromAttribute($id, "CriticalVariables", $CriticalVariableList["ObjectID"]);',
                                'sort'     => [
                                    'column'    => 'Name',
                                    'direction' => 'ascending'
                                ],
                                'columns' => [
                                    [
                                        'caption' => 'ID',
                                        'name'    => 'ObjectID',
                                        'width'   => '150px',
                                        'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "CriticalVariablesConfigurationButton", "ID " . $CriticalVariableList["ObjectID"] . " bearbeiten", $CriticalVariableList["ObjectID"]);'
                                    ],
                                    [
                                        'caption' => 'Name',
                                        'name'    => 'Name',
                                        'width'   => '300px',
                                    ],
                                    [
                                        'caption' => 'Objektbaum',
                                        'name'    => 'VariableLocation',
                                        'width'   => '700px'
                                    ]
                                ]
                            ],
                            [
                                'type'     => 'OpenObjectButton',
                                'name'     => 'CriticalVariablesConfigurationButton',
                                'caption'  => 'Bearbeiten',
                                'visible'  => false,
                                'objectID' => 0
                            ]
                        ]
                    ],
                    'onClick' => self::MODULE_PREFIX . '_ListCriticalVariables($id);'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Registrierte Referenzen',
                    'bold'    => true,
                    'italic'  => true
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
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " bearbeiten", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px'
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Bearbeiten',
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
                    'bold'    => true,
                    'italic'  => true
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
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " bearbeiten", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px'
                        ],
                        [
                            'caption' => 'Objektbaum',
                            'name'    => 'VariableLocation',
                            'width'   => '700px'
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
                    'caption'  => 'Bearbeiten',
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

    ######### Private

    /**
     * Counts the rows of a list.
     *
     * @param string $ListName
     * @return int
     * @throws Exception
     */
    private function CountRows(string $ListName): int
    {
        $elements = json_decode($this->ReadPropertyString($ListName), true);
        $amountRows = count($elements) + 1;
        if ($amountRows == 1) {
            $amountRows = 3;
        }
        return $amountRows;
    }

    /**
     * Gets the colors for all rows of a list.
     *
     * @param string $ListName
     * @return array
     * @throws Exception
     */
    private function GetListValues(string $ListName): array
    {
        $values = [];
        if ($ListName == 'VariableLinkList') {
            $name = 'TriggerList';
        } else {
            $name = $ListName;
        }
        $elements = json_decode($this->ReadPropertyString($name), true);
        foreach ($elements as $element) {
            $rowColor = '#C0FFC0'; //light green
            if (!$element['Use']) {
                $rowColor = '#DFDFDF'; //grey
            }
            $id = $element['ID'];
            if ($id <= 1 || !@IPS_ObjectExists($id)) {
                $rowColor = '#FFC0C0'; //red
            }
            if ($ListName == 'VariableLinkList') {
                $values[] = ['SensorID' => $element['ID'], 'Designation' => $element['Designation'], 'Comment' => $element['Comment']];
            } else {
                $values[] = ['rowColor' => $rowColor];
            }
        }
        return $values;
    }

    /**
     * Counts the elements of a list.
     *
     * @param string $ListName
     * @return int
     * @throws Exception
     */
    private function CountElements(string $ListName): int
    {
        return count(json_decode($this->ReadPropertyString($ListName), true));
    }
}