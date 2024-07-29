const dotenv = require('dotenv');
dotenv.config();
const OpenAI = require('openai');
const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
  apiKey
});

const title = process.argv[4];
const description = process.argv[2];
const assistantId = 'asst_MBF7OVPHuB6HwKZaaff5pnkm'; 

const checkRunStatus = async (threadId, runId) => {
  try {
    const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
    return runStatus;
  } catch (error) {
    console.error('Error checking run status:', error);
  }
};

const main = async () => {
  try {
    const thread = await openai.beta.threads.create();
    const threadId = thread.id;

    await openai.beta.threads.messages.create(threadId, {
      role: "user",
      content:"Here is the title[" + title + "]. Here is the description of service [" + description + "]"
      ,
    });

    const run = await openai.beta.threads.runs.create(threadId, {
      assistant_id: assistantId
    });

    let runStatus;
    do {
      await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
      runStatus = await checkRunStatus(threadId, run.id);
    } while (runStatus.status !== 'completed');

    if (runStatus.status === 'completed') {
      const messages = await openai.beta.threads.messages.list(threadId);
      for (const message of messages.data.reverse()) {
        if (message.role === 'assistant') {
            let jsonResult = message.content[0].text.value;
            console.log(jsonResult);
        }
      }
    } else {
      console.log(runStatus.status);
    }

  } catch (error) {
    console.error('Error uploading file or interacting with OpenAI:', error);
  }
};

main();
