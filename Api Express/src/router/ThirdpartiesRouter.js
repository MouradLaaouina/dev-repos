import Router from "express"
import { getWithToken, postWithToken, putWithToken, deleteWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const thirdPartiesRouter = Router()

thirdPartiesRouter.get("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const client_type = req.query.client_type || 1
    try{
        // Use custom endpoint that returns only clients associated with the logged-in sales rep
        let url = `/salesrepclients/salesreps/clients?client_type=${client_type}`;
        const clients = await getWithToken(url, token);

        res.send({
            status:"ok",
            result: responseToDTO(clients, null)
        });
    }catch(error){
        res.send({
            status:"error",
            message: error.message
        });
    }
})

thirdPartiesRouter.get("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const id = req.params.id
    try{
        const response = await getWithToken(`/thirdparties/${id}`,token)
        res.send({
            status:"ok",
            result: responseToDTO(response,null)
        })
    }catch(error){
        res.send({
            status:"error"
        })
    }
})

thirdPartiesRouter.get("/:id/fixedamountdiscounts", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const id = req.params.id
    try{
        let url = `/thirdparties/${id}/fixedamountdiscounts`
        const response = await getWithToken(url,token)
        res.send({
            status:"ok",
            result: responseToDTO(response,null)
        })
    }catch(error){
        res.send({
            status:"error"
        })
    }
})

thirdPartiesRouter.get("/:id/representatives", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const id = req.params.id
    try{
        let url = `/thirdparties/${id}/representatives`
        const response = await getWithToken(url,token)
        res.send({
            status:"ok",
            result: responseToDTO(response,null)
        })
    }catch(error){
        res.send({
            status:"error",
            message: error.message
        })
    }
})

thirdPartiesRouter.post("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await postWithToken("/thirdparties", req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

thirdPartiesRouter.put("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await putWithToken(`/thirdparties/${req.params.id}`, req.body, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

thirdPartiesRouter.delete("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    try{
        const response = await deleteWithToken(`/thirdparties/${req.params.id}`, token)
        res.send({
            status:"ok",
            result: response
        })
    }catch(error){
        res.status(500).send({
            status:"error",
            message: error.message
        })
    }
})

export default thirdPartiesRouter
