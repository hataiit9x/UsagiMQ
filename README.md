# UsagiMQ
A simplest Message Queue by using Redis and PHP in a single box (one class, no aditional dependency)

## Why i should use a Message Queue (MQ)?

Lets say the next example, a system where one system sends information to another, for example a web client and a web service.  

If the webservice is doing a **slow operation and its receiving the information of many clients** at once then, sooner or later, the system could collapses or bottleneck.

For example, if every client uses 0.1 second to do the operation (receiving the information and storing in the database), and we have 1000 customers then, every operation takes 1.6 minutes. This not the ideal for most cases.


![Web Service](visio/WebService.jpg "Web Service")

The solution is to add a Message Queue to the system. A Message Queue is only a server that stores messages/operations received by a PUBLISHER and later a SUBSCRIBER could executes.

For the same example, a PUBLISHER (former client) could uses 0.001 to call the MQ. Then the SUBSCRIBER could do all the operations, for example every hour/at night without worry if all the operations take many minutes or hours.


![MQ](visio/MQ.jpg "MQ")

The MQ should be as fast as possible and be able to listen and store the request (envelope) of every publisher. However, the MQ is not doing the end operation, its similar to a email server. 
Later, a subscriber could read this information and process as correspond.

The drawback of this method is adding a delay, the process is not executed syncronously but asyncronously, and the PUBLISHER don't know really if the information was processed correctly by the SUBSCRIBER.

## Considerations

This library uses Redis. Redis is an open source (BSD licensed), in-memory data structure store, used as a database, cache and message broker.

## Why UsagiMQ?

While there are many Message Queue in the market (including open source / freeware / commercial) but most of them are heavyweight.
UsagiMQ is lightweight, thinking in customization. You could optimize and customize it for your needing, for example, changing the structure of envelope.

**It requires a single file (UsagiMQ.php)**

## Envelope structure

id = the identified of the envelope (required).   
from = who send the envelope (optional)   
body = the content of the envelope (required).   
date = date when it was received. (required, generated)   
try = number of tries. (required, for use future, generated)   


## MQ Server (where the envelope are stored)

Example:
``` 
include "UsagiMQ.php";  
$usa=new UsagiMQ("127.0.0.1",6379,1);
if ($usa->connected) {
  echo $usa->receive();
} else {
  echo "not connected";
}
```
## Subscriber (local)

Its a local subscriber (has access to Redis). However, it could be used for to create a remote subscriber.

Example
``` 
<?php
// its a local subscriber
include "UsagiMQ.php";

$usa=new UsagiMQ("127.0.0.1",6379,1);
if (!$usa->connected) {
    echo "not connected";
    die(1);
}

$listEnveloper=$usa->listPending("insert");

foreach($listEnveloper as $id) {
    $env=$usa->readItem($id);
    var_dump($env);
    // here we process the envelope
    // and if its right then we could delete.
    //$usa->delete($id);
}
```

# Commands

## Constructor

> $usa=new UsagiMQ($IPREDIS,$PORT,$DATABASE);

$IPREDIS indicates the IP of where is the redis server.
$PORT indicates the REDIS port.
$DATABASE (optional), indicates the database of redis (0 is the default value)


## Todo

- Error control / Log
- Readme missing the command
- Readme missing the PUBLISHER.
- 

 