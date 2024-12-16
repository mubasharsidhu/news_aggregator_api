# News Aggregator API

RESTful API for a news aggregator service that pulls articles from various sources and provides endpoints for a frontend application to consume.

As a sample, following three APIs are integrated:

- News API
  ```
  https://newsapi.org/docs
  ```
- The Guardian
  ```
  https://open-platform.theguardian.com/documentation
  ```
- New York Times
  ```
  https://developer.nytimes.com/docs/articlesearch-product/1/overview
  ```

## Getting Started

### Dependencies

- Docker Engine: v27.3.1 or higher. You may download it from [here](https://docs.docker.com/engine/release-notes/27/)

### Installing

- #### Setup

  - Clone the repository using terminal

    ```
    git clone https://github.com/mubasharsidhu/news_aggregator_api.git
    ```

  - OR download the code from [this repo](https://github.com/mubasharsidhu/news_aggregator_api) and unzip it

- #### Terminal

  - Open the unzipped/cloned `news_aggregator_api` folder in terminal to run the commands

  - Run the following command and press enter
    ```
    ls
    ```
  - Make sure you can see the `Makefile` file in the list

- #### Environment

  - Rename the `.env.sample` file to `.env` and update the environment variables i.e. database name and passwords, Or leave as it is.

- #### News API Keys

  You may use the my currently added News sources API keys (in .env file) for testing.

  OR

  Obtain your own API keys from following links

  - News API ([Link here](https://newsapi.org/register))

    Add the following environment variable to .env file

    ```
    NEWS_API_KEY=<Your API Key Here>
    ```

  - The Guardian ([Link here](https://open-platform.theguardian.com/access))

    ```
    GUARDIAN_API_KEY=<Your API Key Here>
    ```

  - New York Times ([Link here](https://developer.nytimes.com/accounts/create))

    And enable/authorise the API key for `Article Search API`

    ```
    NYT_API_KEY=<Your API Key Here>
    ```

- #### Execution
  - Now run the following command and press enter
  ```
  make setup
  ```
