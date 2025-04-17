# Lemmein

Manage an IP list for firewalls to update your IP from outside.

## Getting Started

Configuration is stored as JSON files inside the **data/** directory.

1. Create a minimal **list.json** with the connections to be managed:

   ```json
   {
     "name": "My List",
     "connections": {
       "4G": {}
     }]
   }
   ```

2. Optionally define local/static subnets for the list with the `networks` key:

   ```json
     "networks": {
       "Home": "192.168.1.0/24",
       "Work": "123.45.100.48/28"
     }
   ```

   > Static networks are effectively read-only.
   > They're included in list output, but *not* on the ping page.

3. Create a **config.json** to set your timezone.
   If you're behind a proxy, configure its IP too:

   ```jsonp
   {
     "timezone": "Europe/London",
     "proxy_ips": [
       "127.0.0.1"
     ]
   }
   ```

## Authentication

Lemmein can optionally extract user data from HTTP headers set by an upstream
auth provider. In the following examples it's assumed you're using Authentik.

### Dashboard and Ping

1. Create and assign a group that contains **all** dashboard *and* ping users.
2. Enable header checks by adding the following to **data/config.json**:

    ```json
      "auth_header": "X_AUTHENTIK",
      "show_uids": true
    ```

3. Login with Authentik and you'll see an "Access denied" message.  
   > To see an error you **must** enable `show_uids`.
   >
   > When disabled, non-admin users accessing the dash are simply redirected to ping.
4. Copy your Authentik UID into the admins list.
   If you're the only user, you can also disable UIDs:

    ```json
      "auth_header": "X_AUTHENTIK",
      "admins": ["104cc0c42304687da5a597bd6cba2ebeadd2d93691802abb4d77c3f07832ca22"]
    ```

#### Restricting Connection Pings

Each connection can optionally have pings restricted to specific usernames:

```json
{
  ...
  "connections": {
    ...
    "Sally's Laptop": {
      "users": ["sally"]
    }
  }
}
```

> Admins can always ping all connections. If, however, a connection has
> *no `users` key*, it'll be **pingable by *all*** authenticated users.
>
> To lock a connection to *"only admins"* you **must**
> define an empty array, i.e. `"users": []`.

### List Access

In **list.json**, any IPs that need access to the list can be defined as `consumers`:

```json
{
  "name": "My List",
  "consumers": ["192.168.1.254", "127.0.0.1"]
  "connections": { ... }
}
```

To avoid being redirected when accessing the list from e.g.
a firewall, add `^/list\.php*` to your proxy's no-auth list.  
In Authentik, it's the **Advanced Protocol settings >
Unauthenticated paths** provider option.
