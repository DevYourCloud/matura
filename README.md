# Matura

Light firewall to only allow known devices to access your server 


## ForwardedAuth with Traefik 

In order to use Matura with Traefik, you have to setup traefik as follow. 

Define a new Middleware for Traefik : 

```
label:
    - "traefik.http.middlewares.test-auth.forwardedauth.address=https://example.com/auth"
```

Bind the middleware with traefik entrypoints

```
label:  
      - "--entrypoints.websecure.http.middlewares=forwardedauth"
      - "--entrypoints.websecure.forwardedheaders.insecure=true" # in order to get the real ip
```