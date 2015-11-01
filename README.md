# FormStack-CommandLine

Command line tool for querying FormStack forms

This tool can query and save reports on forms and submissions for your FormStack account

Install Instructions:

This tool is dockerized.
- Clone the prject
- run: docker build -t formstack-command-line .
- run: docker run -it --rm --name formstack-running-command-line formstack-command-line

If docker is not available be sure to have PHP version > 5.5 and run: php run.php
