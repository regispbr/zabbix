# Host Group Status Widget for Zabbix 7.0

## Overview
The Host Group Status widget displays the count of hosts in a selected host group based on their alarm status. It provides a simple, visual way to monitor host health at a glance.

## Features
- **Flexible Host Counting**: Choose to count:
  - Hosts with active alarms
  - Hosts without alarms
  - All hosts in the group
- **Customizable Appearance**: 
  - Select custom widget background color
  - Adjustable font size and family
  - Optional borders with configurable width
  - Customizable padding
- **Group Display Options**:
  - Show/hide group name
  - Custom group name override
- **Interactive Features**:
  - Click to view host list
  - Optional custom URL redirect
  - Hover effects for better UX
- **Auto-refresh**: Updates every 30 seconds

## Installation
1. Copy the `hostgroupstatus` folder to your Zabbix modules directory:
   ```
   /usr/share/zabbix/modules/
   ```
2. Set proper permissions:
   ```
   chown -R www-data:www-data /usr/share/zabbix/modules/hostgroupstatus
   chmod -R 755 /usr/share/zabbix/modules/hostgroupstatus
   ```
3. Restart your web server (Apache/Nginx)
4. The widget will appear in the dashboard widget list as "Host Group Status"

## Configuration Options

### Data Source
- **Host groups**: Select one or more host groups to monitor (required)
- **Hosts**: Optionally filter to specific hosts within the selected groups
- **Count Mode**: Choose what to count:
  - Hosts with alarms (default)
  - Hosts without alarms
  - All hosts

### Appearance
- **Widget Color**: Select the background color for the widget (default: green #4CAF50)
- **Show group name**: Display the group name in the widget
- **Custom group name**: Override the group name with custom text
- **Font Size**: Adjust text size (default: 14px)
- **Font Family**: Change font family (default: Arial, sans-serif)
- **Show Border**: Enable/disable border
- **Border Width**: Set border thickness (default: 2px)
- **Padding**: Adjust internal spacing (default: 10px)

### Behavior
- **Enable URL redirect**: Redirect to custom URL on click
- **Redirect URL**: Custom URL to open when widget is clicked
- **Open in new tab**: Open links in new browser tab

## Usage Examples

### Example 1: Monitor Critical Hosts
- **Count Mode**: Hosts with alarms
- **Widget Color**: Red (#E45959)
- Shows how many hosts currently have active alarms

### Example 2: Track Healthy Hosts
- **Count Mode**: Hosts without alarms
- **Widget Color**: Green (#4CAF50)
- Shows how many hosts are running without issues

### Example 3: Total Host Count
- **Count Mode**: All hosts
- **Widget Color**: Blue (#2196F3)
- Shows total number of hosts in the group

## Compatibility
- Zabbix 7.0.13
- Uses standard Zabbix 7.0 widget framework classes
- Compatible with Zabbix dark theme

## Technical Details
- **Namespace**: Modules\HostGroupStatus
- **Widget Class**: WidgetHostGroupStatus
- **Refresh Interval**: 30 seconds
- **API Calls**: Uses Zabbix API (Host.get, HostGroup.get, Trigger.get)

## Troubleshooting
- If the widget doesn't appear, check file permissions
- Verify that the module directory path is correct
- Check Zabbix frontend logs for any errors
- Ensure your Zabbix version is 7.0 or higher

## License
GNU General Public License v2.0

## Author
Reginaldo Costa

## Version
1.0