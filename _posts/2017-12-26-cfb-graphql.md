---
layout: post
title:  CFB & GraphQL
description: Creating a searchable database of CFB games using GraphQL, Node, SQL, and ESPN.
permalink: /cfb-and-graphql/
---

## Introduction

Over the last few months, I've been poking around ESPN's private sports data API and documenting bits and pieces that may be useful in a project someday. I'd been searching for the right thing to work on to make the most use of this data source, and on Christmas morning, the right idea finally came to me.

It's been a long time since the [College Football API](https://collegefootballapi.com/) has been active, and historical college football game data in a developer-friendly format has been hard to come by as of late. I like taking on projects that meld technologies that I want to learn with sports, so I felt like I could kill two birds with one stone here: building a easily searchable historical database of games while learning GraphQL and strengthening my SQL skills.

## Project Goals and Milestones

I have two key goals for this project:

* Be a static and complete source of college football game data.
* Provide a easy-to-use API to retrieve the data (via GraphQL).

Using these points, I came up with discrete milestones:

1. **Setting up the database and server**: Creating a PostgreSQL database using [Postgres.app](http://postgresapp.com) and hooking it up to a [NodeJS](https://nodejs.org/) server powered by [Express](https://github.com/expressjs/express).

2. **Backfilling the database**: Querying ESPN for game data for every day that games were played since 1936 (since ESPN's data goes that far back) and then storing this in SQL. This part looks something like this:
    - Execute a HTTP GET request to ESPN's API and retrieving data for a date.
    - Clean and reformat the data to match SQL table schemas.
    - Store this data in SQL.
    - Note that the database was updated.
    - Repeat this process over and over again for every valid game-day since August 1936.

3. **Updating/'Forefilling' the database**: Querying ESPN every day moving forward to update existing records and add new ones. This part looks quite a bit the same as the previous, but instead of repeating this for past game-days, it's repeated for future ones. Specifically:
    - Check if there has been a database update for the day.
    - If there hasn't, retrieve data for the current day and the previous day (since this is being run daily, in theory).
    - Clean and reformat this data to match SQL table schemas.
    - Store this data in SQL, updating records where necessary.
    - Note that the database was updated.

4. **Querying via GraphQL**: Implementing the new hotness in APIs to allow for easy search via a HTTP POST request, which would require that I:
    - Install and setup GraphQL.
    - Create object schemas.
    - Create query schemas.
    - Test data retrieval via HTTP POST endpoint.

## Things I've Learned

There are a few things in this project that had steep learning curves as I built out things. Here's what I've learned so far.

### GraphQL

GraphQL is very powerful, but also very tricky to get setup initially. I found a [list of tools to build GraphQL supported databases](https://github.com/dbohdan/automatic-api) and tried using [PostGraphQL](https://github.com/postgraphql/postgraphql), but I couldn't wrap my head around how to use it. I then decided to roll my own PostgreSQL + GraphQL integration. Below are a couple of things I had trouble with.

**Custom scalars (specifically, dates) and object schemas**

GraphQL supports five scalar types types out of the box: `Int`, `Float`, `String`, `Boolean`, and `ID` (a unique identifier string). But for my needs, I needed another type to recreate my SQL table schema for the Game object in GraphQL: dates. Thankfully, I found the [graphql-scalars](https://github.com/adriano-di-giovanni/graphql-scalars) npm module, which provides support for dates and email addresses in `GraphQLObjectSchema`. However, I had trouble rolling this into one of my schemas. This is what I wanted to have setup:

```javascript
// A very simple basic schema for representing a game,
// without getting into the nitty-gritty of saving individual
// pieces of game metadata.
scalar Date
type Game {
    espn_id: String,
    retrieved_at: Date,
    json: String
}
```

Then I wanted to throw that string into GraphQL's `buildSchema()` to auto-generate a `GraphQLObjectSchema` object, as per [this tutorial from Apollo](https://www.apollographql.com/docs/graphql-tools/scalars.html#Using-a-package). But this didn't exactly work: `Date` wasn't a defined scalar type that I could access.

Instead, I built the schema from scratch, instantiating a new `GraphQLObjectType` object for `Game`. It ended up looking something like this:

```javascript
var GameType = new graphql.GraphQLObjectType({
    name: 'Game',
    description: 'A respresentation of a college football game in ESPN\'s system.',
    fields: {
        espn_id: {
            type: graphql.GraphQLString,
            description: 'The ID of the game in ESPN\'s database.'
        },
        retrieved_at: {
            type: GraphQLDate,
            description: 'When the game was retrieved from ESPN.'
        },
        json: {
            type: graphql.GraphQLString,
            description: 'The raw JSON from ESPN.'
        }
    }
});
```

I naively tried to use this as a Schema in `graphqlHTTP()`, but `graphiql` politely informed me that it was 1) not valid and 2) didn't have a query type. I added this snippet:

```javascript
// Defining the GameQuery type
var queryType = new graphql.GraphQLObjectType({
  name: 'GameQuery',
  description: 'A GraphQL Query object used to search the cfb-graphql PostgreSQL database.',
  fields: {
    game: {
      type: GameType,
      args: {
        id: { type: graphql.GraphQLString }
      },
      resolve:  (_, {id}) => {
          // SELECT records from the db where espn_id matches
          db.select('SELECT * from cfb_espn.games WHERE espn_id = $1', [id], (err, res) => {
             if (err) {
                 console.error(err);
             }

             return res.rows[0];
          });
      }
    }
  }
});
```

Then, I created an actual `GraphQLSchema` object and sent this along to `graphqlHTTP()`. Now, `graphiql` was all happy and I could search through my database easily via GraphQL, right? This brings me to learning moment \#2.

**Promises**

I was using `node-postgres` to manage my PostgreSQL database from Node, but as wonderful as it is, it presented an issue: the GraphQL resolver function I had written `GameQuery` ended its execution before the `node-postgres`-powered database call returned its data. [GraphQL docs](http://graphql.org/learn/execution/#asynchronous-resolvers) stated that if I returned a `Promise` as part of my resolver function, then the function would not finish execution until the `Promise` had been resolved. Seems easy enough, right?

Well, `node-postgres` doesn't support `Promises` out of the box, so I had to find another `npm` module to do the job. I also wanted to limit the amount of code I would have had to rewrite - I had already written `db/refresh-db.js` and `db/setup-db.js`, which had a lot of direct database calls inside (which, in retrospect, I could have *probably* avoided, had I built things better...).

In comes [`pg-promise`](https://github.com/vitaly-t/pg-promise), a drop-in replacement for `node-postgres` that supports `Promises`. After minor code changes to database calls, I modified `GameQuery`'s resolver function to be:

```javascript
resolve:  (_, {id}) => {
  return new Promise((resolve, reject) => {
      db.select('SELECT * from cfb_espn.games WHERE espn_id = $1', [id])
      .then(result => {
          resolve(result);
      })
      .catch(error => {
          if (error) {
              console.error(error);
              reject(error);
          }
      });
  });
}
```

Now, since I was returning a `Promise` and running my database calls asynchronously, I could successfully get data using GraphQL queries in `graphiql`. Huzzah!

## Helpful Links

Here are some links I found useful while working on this project early on. Hopefully they'll be of some use!

[GraphQL](https://graphql.org/)

[express-graphql](https://github.com/graphql/express-graphql)

[Using GraphQL with MongoDB](https://www.compose.com/articles/using-graphql-with-mongodb/)

[Building a GraphQL server with Node.js and SQL](https://www.reindex.io/blog/building-a-graphql-server-with-node-js-and-sql/)

[Custom Scalars and Enums](https://www.apollographql.com/docs/graphql-tools/scalars.html)

[Constructing Types](http://graphql.org/graphql-js/constructing-types/)

[Running an Express GraphQL  Server](http://graphql.org/graphql-js/running-an-express-graphql-server/)

## Conclusion

This project is super-basic right now, and I plan to keep improving it in my free time. Eventually, I plan to expand the SQL schema for games and add support for teams and other important metadata. Long-term, I envision a complete database of college football games with a nice front-end so that anyone can leaf through the database and refer to it if they're having a conversation about a matchup or interested in reading up on some historic games.

Like I said earlier, I love finding new ways to integrate my interests in sports and programming, and I find this experience truly valuable as a GraphQL and SQL learning experience for me and as a treasure trove of data about college football to explore.

If you're interested in reading through the code, it's available on [GitHub](https://github.com/akeaswaran/cfb-graphql). It's not very well-documented, but I hope to improve documentation as I work on things.
