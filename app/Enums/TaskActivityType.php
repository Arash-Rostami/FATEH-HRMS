<?php

namespace App\Enums;

enum TaskActivityType: string
{
    case Comment = 'comment';
    case StatusChange = 'status_change';
    case Assignment = 'assignment';
    case Archive = 'archive';
    case Approval = 'approval';
    case Attachment = 'attachment';
    case ResponsibleChange = 'responsible_change';
    case DepartmentChange = 'department_change';
    case StateChange = 'state_change';
    case DeadlineChange = 'deadline_change';
    case PriorityChange = 'priority_change';
    case LabelChange = 'label_change';
    case ProjectChange = 'project_change';
    case MetaChange = 'meta_change';
    case SettingsChange = 'settings_change';
}
