FROM golang:latest

RUN go install github.com/go-delve/delve/cmd/dlv@latest

WORKDIR /app
COPY . .
RUN go mod download
# RUN go build -gcflags="all=-N -l" -o /app
EXPOSE 2345 80