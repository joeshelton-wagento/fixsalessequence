<h2>Description</h2>

This Magento CLI command will create the proper sales sequence tables for a given store. In various circumstances, these tables are not properly created. This causes checkout ot fail with the error below:

```SQLSTATE[42000]: Syntax error or access violation: 1103 Incorrect table name '', query was: INSERT INTO `` () VALUES ()```

<h2>Usage</h2>

Specify a store ID as the main argument, eg:

```bin/magento wagento:fixsalessequence 3```

Or, use the '--all' option to create missing tables for all stores. The command will not overwrite existing tables.

```bin/magento wagento:fixsalessequence --all```

<h2>Requirements</h2>

The command has been tested on Magento 2.2.0 with PHP 7.0. It has not been tested by the author on any other versions.