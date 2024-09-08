import 'dotenv/config';
import express from "express";
import helmet from "helmet";

const app = express();
app.use(helmet());
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// Check
app.get(`/check`, (req, res) => {
  res.send("Server is running");
});
app.get(`*`, (req, res) => {
  if (!req.body.language) {
  	res.status(400);
    res.send("No language specified");
    return;
  }
  if (!req.body.code) {
  	res.status(400);
    res.send("No code specified");
    return;
  }
  let runtimes = await fetch('https://emkc.org/api/v2/piston/runtimes').then(res => res.json());
  let values = {
  	language: req.body.language,
  	version: 'N/A',
  	code: req.body.code
  }
  let foundRuntime = false;
  for (let runtime of runtimes) {
  	let runtimeNames = [runtime.language, ...runtime.aliases];
  	for (let runtimeName of runtimeNames) {
  		if (runtimeName.toLowerCase() == req.body.language.toLowerCase()) {
  			values.version = runtime.version;
  			foundRuntime = true;
  		}
  	}
  }
  if (!foundRuntime) {
  	res.status(404);
    res.send("Runtime not found");
    return;
  }
  let payload = {
  	language: values.language,
  	version: values.version,
  	files: [
  		{
  			content: values.code
  		}
  	]
  };
  let executeResult = await fetch('https://emkc.org/api/v2/piston/execute', {
  	method: 'POST',
  	body: JSON.stringify(payload)
  }).then(res => res.json());
  let response = '';
  if (executeResult.compile) {
  	if (executeResult.compile.code) {
  		if (response.length <= 1) {
  			response = `Compile-Output: (Code ${executeResult.compile.code}): ${executeResult.compile.output}`;
  		} else {
  			response = `; Compile-Output: (Code ${executeResult.compile.code}): ${executeResult.compile.output}`;
  		}
  	}
  }
  if (executeResult.run) {
  	if (response.length <= 1) {
  	  			response = `Run-Output: (Code ${executeResult.run.code}): ${executeResult.run.output}`;
  	  		} else {
  	  			response = `; Run-Output: (Code ${executeResult.run.code}): ${executeResult.run.output}`;
  	  		}
  }
  if (response.length > 499) {
  	respponse = response.substring(0, 499);
  }
  res.send(response.trim());
});
app.use("/", (req, res, next) => {
  res.status(404); // Unknown route
  res.send("Unknown route");
});

app.listen(process.env.PORT);
console.log("Server listening on port 1337");

export default app;
