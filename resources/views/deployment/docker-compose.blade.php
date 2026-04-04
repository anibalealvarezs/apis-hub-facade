services:
  {{ $subdomain }}:
    build:
        context: .
        dockerfile: Dockerfile
    container_name: apis-hub-{{ $subdomain }}
    restart: always
    env_file: .env
    environment:
        - INSTANCE_NAME=apis-hub-{{ $subdomain }}
    networks:
      - apis-hub-network
    ports:
      - "{{ $externalPort }}:8080"
    labels:
      - "caddy={{ $domain }}"
      - "caddy.reverse_proxy={{ $subdomain }}:8080"

networks:
  apis-hub-network:
    external: true
