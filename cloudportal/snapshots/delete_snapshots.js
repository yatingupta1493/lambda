'use strict';
var AWS = require('aws-sdk');
console.log('Loading function');
exports.handler = (event, context, callback) => {
    var number = (event.number === undefined ? 'No-Number' : event.number);
    var region = (event.region === undefined ? 'us-east-1' : event.region);
    var Snapshot_Id = (event.Snapshot_Id === undefined ? 'No-Instance Id' : event.Snapshot_Id);
    var Description = (event.Description === undefined ? 'No-Description' : event.Description);
    var Instance_Name = (event.Instance_Name === undefined ? 'No-Instance name' : event.Instance_Name);
    var accessKey = (event.accessKey === undefined ? 'No-accesskey' : event.accessKey);
    var secretKey = (event.secretKey === undefined ? 'No-secretkey' : event.secretKey);
    if (accessKey === "No-accesskey" || accessKey === null || secretKey === "No-secretkey" || secretKey ===null) {
        var ec2 = new AWS.EC2({apiVersion: '2017-11-15',region: region});
        console.log("test1");
    } else {
        var ec2 = new AWS.EC2({apiVersion: '2016-11-15',region: region, accessKeyId: accessKey, secretAccessKey: secretKey});
        console.log("test2");
    }
    
    var params = {
        
	    "SnapshotId": Snapshot_Id /* required */        
        //"DryRun": true /* To test if user has permission to delete before actual deletion */
    };
    ec2.deleteSnapshot(params, function(err, data) {
        if (err) console.log(err, err.stack); // an error occurred
        else {
            callback(null, "Snapshot deleted successfully");  // successful response
        }
    });
};
