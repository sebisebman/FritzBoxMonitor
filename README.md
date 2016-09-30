# FritzBoxMonitor
This simple php Script shows the remaining data-volume in a pie chart overview. 
The Data is taken directly from the FritzBox via UPnP, which must be enabled.

There are 4 variables that might need adjustment:

$fritzbox_adress = 'fritz.box';
-> 'fritz.box' or IP of Fritz Box

$dataformat = 'igdupnp';
-> 'igdupnp' or 'upnp' depending on Version of Fritz Box

$max_volume = 1000;
-> Datavolume in GB, default 1000 (=1 TB), might be 1024?

$correction = 0.81;
-> correction factor to adjust values, default: 0.81
