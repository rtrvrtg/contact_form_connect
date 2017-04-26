# Contact Form Connect

A Drupal 8 module for sending contact form submissions to other web services.

Written by [Geoffrey Roberts](mailto:g.roberts@blackicemedia.com)

## Introduction

The Contact Form Connect module allows copies of contact form submissions
to be sent to other web services for storage or use in custom workflows.

* To submit bug reports and feature suggestions, visit the Github page:  
  [https://github.com/rtrvrtg/contact_form_connect](https://github.com/rtrvrtg/contact_form_connect)

## Requirements

Your version of Drupal 8 must be recent enough to be able to install
dependencies from Composer, or you should have Composer Manager installed.

This module depends on the following modules:

* Contact
* Contact Storage

The following services are supported out of the box:

* JIRA  
  This connector assumes that you have a user who is allowed to  
  create issues.
* Google Spreadsheets  
  This implementation assumes that you are using a service account with  
  domain-wide access.

## Installation

Install as you would any other Drupal 8 module. See this page
for more info: [https://www.drupal.org/docs/8/extending-drupal-8/installing-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules)

## Configuration

You can add new connectors in the Contact Form Connector Configuration page
at /admin/config/system/contact_form_connector.

Each connector has the following properties:

* a label (and unique machine name)
* a service it connects to (such as JIRA or Google Spreadsheets)
* an endpoint URL (which isn't needed for some services)
* a service username (such as the ID of a service user)
* a service password (which isn't needed for some services)

Once a service is added, it can be added to a contact form at /admin/structure/contact/contact_form_connect/{machine name of form}

Clicking *Add connector* will let you select one of the connectors you
created to add an instance of that connector to this form. (Due to an issue
we haven't yet fixed, you'll need to save the configuration before you can
add any settings.)

The following connector types have the following instance settings.

### Google Spreadsheet

* Document ID (can be found in the URL to your spreadsheet)
* Path to the credentials JSON file (can be downloaded through Google Developer Console)
* A domain username to masquerade as when the connector edits the file
* An app name (which can be left as drupal-contact-form-connect)
* A sheet ID

### JIRA

* Project Key
* Issue summary (accepts tokens from the contact message  
  such as `[contact_message:name]`)
* Task type ID (the numeric ID of the task type you want to add -  
  can be determined by using the JIRA REST API to list issue types.)

## License

This module is licensed under the GNU General Public License v2.

See the LICENSE.md file for the full details.

## TODO

* Implement hooks to let other devs add their own services
* More service types
* Nicer pipelines to transform contact message entities into forms said  
  services can understand
* Fix up the connector settings forms so they don't need to be saved  
  before setting values
* Make admin UI nicer, to make it clearer what fields are required  
  (or not)
* Actually write some proper tests
* Do some code standards checks
* Better docs
* Just make the whole thing a bit nicer
