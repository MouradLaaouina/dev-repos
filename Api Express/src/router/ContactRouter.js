import Router from "express";
import { getWithToken, postWithToken, putWithToken, deleteWithToken } from "../service/axiosService.js";
import authMiddleware from "../middleware/authMiddleware.js";

const contactRouter = Router();

contactRouter.get("/", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    try {
        const contacts = await getWithToken("/thirdparties?sortfield=t.rowid&sortorder=ASC&limit=100", token);
        res.send(contacts);
    } catch (err) {
        res.status(500).send({ error: "Failed to fetch contacts" });
    }
});

contactRouter.post("/", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const contactData = req.body;
    try {
        const newContact = await postWithToken("/thirdparties", contactData, token);
        res.send(newContact);
    } catch (err) {
        res.status(500).send({ error: "Failed to create contact" });
    }
});

contactRouter.put("/:id", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const contactData = req.body;
    const { id } = req.params;
    try {
        const updatedContact = await putWithToken(`/thirdparties/${id}`, contactData, token);
        res.send(updatedContact);
    } catch (err) {
        res.status(500).send({ error: "Failed to update contact" });
    }
});

contactRouter.delete("/:id", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const { id } = req.params;
    try {
        await deleteWithToken(`/thirdparties/${id}`, token);
        res.status(204).send();
    } catch (err) {
        res.status(500).send({ error: "Failed to delete contact" });
    }
});

export default contactRouter;
