const dotenv = require('dotenv');
dotenv.config();
const fs = require('fs');
const OpenAI = require('openai');
const pdf = require('pdf-parse');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];
const jsonlPath = 'output.jsonl';

const openai = new OpenAI({
  apiKey
});

const pdfPath = '/home/mast9834/laboOlona/public/uploads/cv/' + process.argv[2];

// Fonction pour extraire le texte d'un fichier PDF
const extractTextFromPdf = async (pdfPath) => {
  const dataBuffer = fs.readFileSync(pdfPath);
  const data = await pdf(dataBuffer);
  return data.text;
};

// Fonction pour convertir le texte en format JSONL
const convertTextToJsonl = (text) => {
  const lines = text.split('\n').filter(line => line.trim() !== '');
  const jsonlLines = lines.map(line => JSON.stringify({ text: line }));
  return jsonlLines.join('\n');
};

// Fonction pour vérifier le statut du run
const checkRunStatus = async (threadId, runId) => {
  try {
    const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
    return runStatus;
  } catch (error) {
    console.error('Error checking run status:', error);
  }
};

// Fonction pour traduire le résumé en anglais
const translateSummaryToEnglish = async (text) => {
  try {
    const response = await openai.chat.completions.create({
      model: "gpt-4o",
      messages: [
        { role: "system", content: "You are a translator." },
        { role: "user", content: `Translate the following text to English: ${text}` }
      ]
    });
    return response.choices[0].message.content;
  } catch (error) {
    console.error('Error translating summary:', error);
  }
};

// Fonction pour reformater la réponse en JSON
const reformatResponseToJson = (response) => {
  const jsonResponse = {
    personalInformation: {
      fullName: "",
      contactDetails: { phone: "", email: "" },
      address: "",
      dateOfBirth: "",
      skype: "",
      maritalStatus: "",
      militaryStatus: ""
    },
    professionalSummary: "",
    skills: [],
    experiences: [],
    education: "",
    certifications: "",
    languages: [],
    references: "",
    strengthsAndWeaknesses: { strengths: "", weaknesses: "" },
    englishSummary: ""
  };

  // Extracting information from the response text
  const sections = response.split('###');
  sections.forEach(section => {
    const lines = section.split('\n').map(line => line.trim()).filter(line => line);
    if (lines[0] && lines[0].includes('Informations Personnelles')) {
      lines.slice(1).forEach(line => {
        if (line.includes('Nom complet')) jsonResponse.personalInformation.fullName = line.split(':')[1].trim();
        if (line.includes('Adresse')) jsonResponse.personalInformation.address = line.split(':')[1].trim();
        if (line.includes('Courriel')) jsonResponse.personalInformation.contactDetails.email = line.split(':')[1].trim();
        if (line.includes('Mobile')) jsonResponse.personalInformation.contactDetails.phone = line.split(':')[1].trim();
        if (line.includes('Date de naissance')) jsonResponse.personalInformation.dateOfBirth = line.split(':')[1].trim();
        if (line.includes('Skype')) jsonResponse.personalInformation.skype = line.split(':')[1].trim();
        if (line.includes('Statut marital')) jsonResponse.personalInformation.maritalStatus = line.split(':')[1].trim();
        if (line.includes('Position militaire')) jsonResponse.personalInformation.militaryStatus = line.split(':')[1].trim();
      });
    } else if (lines[0] && lines[0].includes('Résumé Professionnel')) {
      jsonResponse.professionalSummary = lines.slice(1).join(' ');
    } else if (lines[0] && lines[0].includes('Compétences')) {
      jsonResponse.skills = lines.slice(1).map(skillLine => {
        const [title, description] = skillLine.split(':');
        return { title: title.trim(), description: description.trim() };
      });
    } else if (lines[0] && lines[0].includes('Expériences Professionnelles')) {
      jsonResponse.experiences = lines.slice(1).map(expLine => {
        const [title, description] = expLine.split(':');
        return { title: title.trim(), description: description.trim(), dateStart: "", dateEnd: "" };
      });
    }
    // Continue similarly for other sections if required
  });

  // Générer des points forts et faibles à partir du contenu récupéré
  const strengths = [];
  const weaknesses = [];

  if (jsonResponse.professionalSummary.toLowerCase().includes('expert')) {
    strengths.push('Expertise en Web Marketing et SEO');
  }
  if (jsonResponse.skills.some(skill => skill.title.toLowerCase().includes('seo'))) {
    strengths.push('Compétences solides en SEO et référencement naturel');
  }
  if (jsonResponse.experiences.some(exp => exp.title.toLowerCase().includes('rédacteur'))) {
    strengths.push('Expérience en rédaction de contenu optimisé pour le web');
  }

  // Ajouter une faiblesse fictive pour l'exemple
  weaknesses.push('Non spécifiées dans le résumé.');

  jsonResponse.strengthsAndWeaknesses.strengths = strengths.join(', ');
  jsonResponse.strengthsAndWeaknesses.weaknesses = weaknesses.join(', ');

  return jsonResponse;
};

// Fonction principale pour extraire le texte, convertir en JSONL, sauvegarder dans un fichier,
// télécharger le fichier, créer un assistant, créer un thread, ajouter un message et surveiller le run
const main = async () => {
  try {
    const text = await extractTextFromPdf(pdfPath);
    const jsonlContent = convertTextToJsonl(text);
    fs.writeFileSync(jsonlPath, jsonlContent);
    console.log(`JSONL file created at ${jsonlPath}`);

    // Crée un flux de lecture à partir du fichier 'output.jsonl' et télécharge le fichier JSONL
    try {
      const fileUploadResponse = await openai.files.create({
        file: fs.createReadStream(jsonlPath),
        purpose: 'fine-tune'
      });
      console.log('File uploaded:', fileUploadResponse);

      // Crée un assistant
      const assistant = await openai.beta.assistants.create({
        name: "Resume Analyzer",
        instructions: `
        You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and readable format for a recruitment report.

        Your report must be written in French, easy to read, and in a professional tone. Use bullet points to organize the information, and ensure that each section is clearly labeled.

        Include the following sections:

        Personal Information: Full name, contact details (phone number, email address), address, date of birth, skype, marital status, and military status.
        Professional Summary: A brief summary of the candidate's professional background and key skills.
        Work Experience: List of previous jobs, including job title, company name, duration of employment, and key responsibilities.
        Education: Academic background, including degrees obtained, institutions attended, and graduation dates.
        Skills: Key skills relevant to the job position.
        Certifications: Any professional certifications the candidate holds.
        Languages: Languages spoken and proficiency levels.
        References: Contact information for professional references, if provided.
        Strengths and Weaknesses: Based on the candidate's information, generate key strengths and potential weaknesses.

        And other relevant information you can find inside the resume file.

        After that, generate a JSON object with the following structure:
        {
          "personalInformation": { "fullName": "", "contactDetails": {"phone": "", "email": ""}, "address": "", "dateOfBirth": "", "skype": "", "maritalStatus": "", "militaryStatus": "" },
          "professionalSummary": "",
          "skills": [{ "title": "", "description": "" }],
          "experiences": [{ "title": "", "description": "", "dateStart": "", "dateEnd": "" }],
          "education": "",
          "certifications": "",
          "languages": [{ "name": "", "level": "" }],
          "references": "",
          "strengthsAndWeaknesses": { "strengths": "", "weaknesses": "" },
          "englishSummary": ""
        }
        `,
        tools: [{ type: "file_search" }],
        model: "gpt-4o"
      });

      console.log('Assistant created with ID:', assistant.id);

      // Crée un nouveau thread
      const thread = await openai.beta.threads.create();
      const threadId = thread.id;
      console.log('Thread created with ID:', threadId);

      // Ajoute un message pour demander l'analyse du CV
      await openai.beta.threads.messages.create(threadId, {
        role: "user",
        content: "Please analyze the resume and extract key information."
      });

      // Télécharge le fichier PDF au thread
      const fileResponse = await openai.files.create({
        file: fs.createReadStream(pdfPath),
        purpose: 'fine-tune'
      });

      await openai.beta.threads.messages.create(threadId, {
        role: "user",
        content: "Here is the resume file.",
        attachments: [{
          file_id: fileResponse.id,
          tools: [{ type: "file_search" }]
        }]
      });

      // Crée et exécute un run
      const run = await openai.beta.threads.runs.create(threadId, {
        assistant_id: assistant.id
      });

      console.log('Run created with ID:', run.id);

      // Vérifie périodiquement le statut du run
      let runStatus;
      do {
        await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
        runStatus = await checkRunStatus(threadId, run.id);
        console.log('Current run status:', runStatus.status);
      } while (runStatus.status !== 'completed');

      // Liste les messages du thread après la complétion du run
      if (runStatus.status === 'completed') {
        const messages = await openai.beta.threads.messages.list(threadId);
        for (const message of messages.data.reverse()) {
          console.log(`${message.role} > ${message.content[0].text.value}`);

          // Assuming the last message contains the JSON result
          if (message.role === 'assistant') {
            let jsonResult = message.content[0].text.value;

            // Reformate la réponse textuelle en JSON
            const report = reformatResponseToJson(jsonResult);

            try {
              fs.writeFileSync('output.json', JSON.stringify(report, null, 2), 'utf8');
              console.log('JSON output saved to output.json');

              // Extracting the summary for translation
              const summaryInFrench = `
                Informations Personnelles: ${report.personalInformation.fullName}, ${report.personalInformation.contactDetails.phone}, ${report.personalInformation.contactDetails.email}, ${report.personalInformation.address}, ${report.personalInformation.dateOfBirth}, ${report.personalInformation.skype}, ${report.personalInformation.maritalStatus}, ${report.personalInformation.militaryStatus}.
                Résumé Professionnel: ${report.professionalSummary}
              `;

              const englishSummary = await translateSummaryToEnglish(summaryInFrench);
              report.englishSummary = englishSummary;

              fs.writeFileSync('output_with_summary.json', JSON.stringify(report, null, 2), 'utf8');
              console.log('JSON with English summary saved to output_with_summary.json');
            } catch (jsonError) {
              console.error('Error parsing JSON:', jsonError);
            }
          }
        }
      } else {
        console.log(runStatus.status);
      }

    } catch (error) {
      console.error('Error uploading file or interacting with OpenAI:', error);
    }

  } catch (error) {
    console.error('Error:', error);
  }
};

main();
