# Code Formating Standards

## PSR

About four years ago I decided that I wanted my PHP code to follow a uniform standard. While certain libraries (Laravel, Symphony, etc) have their own standards, a quick search on the internet showed that there weren't any clearly defined standards at www.php.net for use outside of a private library. The only standards I was able to find were at https://www.php-fig.org/psr/. Lacking any other options I adopted these standards. 

The only notible change this produced was with bracket placement on function declarations. I'm personally not a fan of how the PSR opts to drop them down below the function name, and this differs from most of the code I see developed. However, in my effort to become standardized I adopted these changes. 

## Now Adopting Standards on a Library by Library Basis

Over the last year I decided to adopt the standard of libraries I'm working with, noteably this meant adopting the WordPress standards (which has a love of blank spaces much more than I do). All that to say, you may see different formatting among the samples listed here as they have been compiled over time.

## Flexible When Coding On A Team

When working with a team, I always opt to use whatever code formatting (code standards) that the team has adopted. Additionally, there are code-formatters and minifiers I use (e.g. PHP_CodeSniffer, Prettier, Webpack, SWC, etc) that are easily configurable to ensure well-formatted and consistent code output.
