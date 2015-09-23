# Yodel Server
## API Documentation
The Yodel server api employs an open access system. For requests such as getting posts and getting comments no key is required. For requests such as posting or logging in an authentication key is required. To receive a key you must contact contact@arctro.com

To get a list of posts for an area from the server use the following format:
`
http://arctro.com/yodel/api/0/0/GET_POSTS/?lat=0&lng=0&radius=0&filter=
`

To get a single post the following format is used:
`
http://arctro.com/yodel/api/0/0/GET_POST/?id=0
`

All requests follow a standard layout, as follows:
`
http://arctro.com/yodel/api/AUTH KEY/SESSION ID/REQUEST/FURTHER DATA
`

Auth key is not required for GET requests
A session id is not required for requests that dont require user access.
