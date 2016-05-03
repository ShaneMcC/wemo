# WEMO Tools

This repo contains tools for collecting data from a collection of belkin wemo insight devices.

The data is collected by probes and then sent to a central server for graphing purposes.

To prevent data-loss, if the central server is unavailable, data will collect on the probes and then be pushed as soon as it becomes available again.

The probes will look for any insight-capable devices on the network at the time they run, and submit the data for any they find.

Currently this only graphs the "instantPower" value, even though the probes collect a lot more than that.
