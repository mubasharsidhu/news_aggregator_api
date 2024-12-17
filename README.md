# News Aggregator API

RESTful API for a news aggregator service that pulls articles from various sources and provides endpoints for a frontend application to consume.

As a sample, following three APIs are integrated:

- [News API](https://newsapi.org/docs)
- [The Guardian](https://open-platform.theguardian.com/documentation)
- [New York Times](https://developer.nytimes.com/docs/articlesearch-product/1/overview)

## Getting Started

### Pre-requisites

Docker Engine: v27.3.1 or higher must be running. You may download it from [here](https://docs.docker.com/engine/release-notes/27/)

### Installation

1. #### Setup

   - Clone the repository using terminal

     ```
     git clone https://github.com/mubasharsidhu/news_aggregator_api.git
     ```

   - OR download the code from [this repo](https://github.com/mubasharsidhu/news_aggregator_api) and unzip it

2. #### Terminal

   - Open the unzipped/cloned `news_aggregator_api` folder in terminal to run the commands

   - Run the following command to make sure you can see the `Makefile` file in the list in root
     ```
     ls
     ```

3. #### Environment

   Rename the `.env.sample` file to `.env`.

   - Update the environment variables i.e. database name and passwords.
   - OR
   - Leave as it is.

4. #### News API Keys

   Add your News sources **API keys** (in `.env` file).

   OR

   Obtain your own API keys from following links and add the relevant environment variable to `.env` file

   - News API ([Link here](https://newsapi.org/register))

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

5. #### Execution

   Now run the following command, press enter and wait for the installation to complete.

   ```
   make setup
   ```

   That's it... Bingo!

##

### How to run the program

- [Scribe](https://scribe.knuckles.wtf/laravel) is used to for Restful API endpoints documentation.
- Once the program is running, the endpoints docs can be accessed on [this URL](http://localhost:8081/docs/)

##

### Program Specifications

- By default the cron jobs execute every night at 12:00 AM to fetch the articles of last 24 hours via news sources APIs
- In case you want to immediately fetch the articles you may run the following command in terminal

  ```
  make fetch-articles
  ```

##

### Testing

You may run the following in terminal to run the tests

```
make run-tests
```

##

### Add a new News Source

To add a new news source to fetch articles, you may simply perform the following steps:

1. Obtain the API key from the news API source (for example `xyztimes`)
2. Add that API key to .env file at root where Makefile is placed
3. Open the config file: `src -> config -> services.php`.

   Find **news_aggregator_api_keys** and put that env variable in config

4. Next, create a news service class to fetch the articles, in `src -> app -> Services -> NewsService`. As per the example the class name should be **XyztimesService**

   Implement the `FetchArticleContract` contract/interface to enforce the data stability/standardization

5. Finally, add the schedule in `src -> routes -> console.php`

   here `--source=xyztimes` is the service class name prefix that you just created to fetch the articles.

   In your case it's `XyztimesService`

   ```
   Schedule::command('articles:fetch --source=xyztimes')->dailyAt('00:00')->withoutOverlapping();
   ```
