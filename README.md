# ThoughtSpot Writer

[![Build Status](https://travis-ci.org/keboola/thoughtspot-writer.svg?branch=master)](https://travis-ci.org/keboola/thoughtspot-writer)

Writes data to [Thoughtspot](https://thoughtspot.com) using the [TSLOAD cli tool](https://docs.thoughtspot.com/4.4/reference/data-importer-ref.html) and [TQL commands](https://docs.thoughtspot.com/4.4/reference/sql-cli-commands.html).
These commands are executed on the server through SSH connection. Therefor SSH credentials are needed to connect to the server instance.

## Configuration

```json
{
  "parameters": {
    "db": {
      "host": "TGOUGHTSPOT_INSTANCE",
      "port": 12345,
      "database": "YOUR_DATABASE",
      "schema": null,
      "user": "DBUSER",
      "#password": "*****",
      "sshUser": "thoughtspot",
      "#sshPassword": "*****"
    },
    "tables": [
      {
        "tableId": "simple",
        "dbName": "simple",
        "export": true,
        "incremental": true,
        "primaryKey": [
          "id"
        ],
        "type": "fact",
        "items": [
          {
            "name": "id",
            "dbName": "id",
            "type": "int",
            "size": null
          },
          {
            "name": "name",
            "dbName": "name",
            "type": "varchar",
            "size": 255
          },
          {
            "name": "glasses",
            "dbName": "glasses",
            "type": "varchar",
            "size": 255
          }
        ]
      }
    ]
  },
  "storage": {
    "input": {
      "tables": [
        {
          "source": "simple",
          "destination": "simple.csv",
          "columns": [
            "id",
            "name",
            "glasses"
          ]
        }
      ]
    }
  }
}
```

## Development

1. Clone this repository:
    `git clone git@github.com:keboola/thoughtspot-writer.git`

2. Create `.env` file with variables:
    ```
    DB_USER=
    DB_PASSWORD=
    DB_HOST=
    DB_PORT=12345
    DB_DATABASE=
    SSH_USER=
    SSH_PASSWORD=
    ```

3. Build image:
    `docker-compose build`

4. Develop using TDD:
    `docker-compose run --rm tests`
