# Lemmein

Manage an IP list for firewalls to update your IP from outside.

## Getting Started

Create a minimal **data/list.json** with the connections to be managed:

```json
{
  "name": "Homelab Access",
  "networks": {
    "Home": "10.2.2.0/24",
    "Work": "123.45.100.48/28"
  },
  "connections": {
    "4G": {}
  }]
}
```

Optionally create a **data/config.json** to set custom options:

```jsonp
{
  "timezone": "Europe/London",
  "proxy_ips": [
    "127.0.0.1"
  ]
}
```
