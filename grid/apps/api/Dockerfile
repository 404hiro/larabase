FROM golang:1.23-alpine AS base

WORKDIR /app
RUN apk add --no-cache ca-certificates

COPY go.mod go.sum ./
RUN go mod download

COPY . .
RUN go build -o /bin/grid-api ./cmd/server

FROM alpine:3.20

WORKDIR /app
RUN apk add --no-cache ca-certificates
COPY --from=base /bin/grid-api /bin/grid-api
COPY --from=base /app/migrations ./migrations

EXPOSE 8080
CMD ["/bin/grid-api"]
