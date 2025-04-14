# Lemmein

Manage an IP list for firewalls to update your IP from outside.

## Getting Started

Create a minimal **data/list.json** with the connections to be managed:

```json
{
  "name": "Homelab Access",
  "connections": {
    "Home": {},
    "Work": {}
    "4G": {}
  }]
}
```

Optionally create a **data/config.json** to set custom options:

```jsonp
{
  "timezone": "Europe/London"
}
```
