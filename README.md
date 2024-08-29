# User Comments Plugin 

This is a plugin for PKP's Open Preprint Server (OPS). Logged in users can post comments on published preprints or other comments.
Comments can be flagged, in which case an email is send to the moderators, which can then either un-flag the comment or hide its contents. For reasons such as transparency and integrity comments will not be removed.


## Requirements

This plugin is tailored to our forthcoming pre-print and open-peer-review platform [socios](https://socios.review). We use tailwind.css for our theme and plugins. That means that the output will be unstyled if one uses the current standard theme.

Users are identified by their ORCID: Thus they need to be registered accordingly. We use the [openID Authentication Plugin](https://github.com/leibniz-psychology/openid) for this.

This plugin uses a custom api. To allow access to the api for non-logged in users to read the comments, an api key from a user account is needed. We created a dedicated account just for this end.  

That said, the plugin is not overly complicated and one should be able to adapt it easily to i.e. the standard theme and remove the ORCID requirement.

## Compatibility

The ops_3-3 branch works only with OPS 3.3.0. However, it should work with OJS if the hook to display the comments on the frontend page is changed from Templates::Preprint::Details to Templates::Article::Main.

## Installation

So far there is no release package. You can clone the repo into your OPS plugin folder (plugins/generic/userComments) or download the source files and create your own release package (i.e. tar --exclude-from=./userComments/tarExclude.txt -czvf userComments_1-0-0-10.tar.gz userComments). 

For the api to be accessible for non-registered users, an api key has to be provided via the plugin's settings form.

# License
__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2024-2025 University of Cologne__
